<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GuestInviteStatus;
use App\Models\Event;
use App\Models\GuestInvite;
use App\Notifications\GuestInvitationNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;

final class GuestInviteService
{
    public function __construct(
        private readonly GuestInviteAccessService $accessService,
        private readonly DashboardMetricsService $metricsService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Event $event, array $data): GuestInvite
    {
        return GuestInvite::query()->create([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'email' => strtolower((string) $data['email']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'invitation_token' => $this->accessService->generateToken(),
            'status' => GuestInviteStatus::Pending,
            'household_name' => $data['household_name'] ?? null,
            'group_label' => $data['group_label'] ?? null,
            'tags' => $data['tags'] ?? [],
            'plus_one_limit' => $data['plus_one_limit'] ?? null,
            'table_id' => $data['table_id'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(GuestInvite $invite, array $data): GuestInvite
    {
        $invite->update([
            'email' => isset($data['email']) ? strtolower((string) $data['email']) : $invite->email,
            'first_name' => $data['first_name'] ?? $invite->first_name,
            'last_name' => array_key_exists('last_name', $data) ? $data['last_name'] : $invite->last_name,
            'phone' => array_key_exists('phone', $data) ? $data['phone'] : $invite->phone,
            'household_name' => array_key_exists('household_name', $data) ? $data['household_name'] : $invite->household_name,
            'group_label' => array_key_exists('group_label', $data) ? $data['group_label'] : $invite->group_label,
            'tags' => $data['tags'] ?? $invite->tags,
            'plus_one_limit' => array_key_exists('plus_one_limit', $data) ? $data['plus_one_limit'] : $invite->plus_one_limit,
            'table_id' => array_key_exists('table_id', $data) ? $data['table_id'] : $invite->table_id,
        ]);

        return $invite->fresh(['table', 'registration']);
    }

    public function revoke(GuestInvite $invite): void
    {
        $invite->update(['status' => GuestInviteStatus::Revoked]);
        $this->metricsService->invalidateForEvent($invite->event);
    }

    public function send(GuestInvite $invite): GuestInvite
    {
        $notifiable = new AnonymousNotifiable;
        $notifiable->route('mail', $invite->email);
        $notifiable->notify(new GuestInvitationNotification($invite));

        $invite->update([
            'status' => GuestInviteStatus::Sent,
            'sent_at' => now(),
        ]);

        $this->metricsService->invalidateForEvent($invite->event);

        return $invite->fresh();
    }

    /**
     * @param  list<int>  $inviteIds
     * @return array{sent: int}
     */
    public function sendBulk(Event $event, array $inviteIds = []): array
    {
        $query = GuestInvite::query()
            ->where('event_id', $event->id)
            ->whereIn('status', [GuestInviteStatus::Pending, GuestInviteStatus::Sent, GuestInviteStatus::Opened]);

        if ($inviteIds !== []) {
            $query->whereIn('id', $inviteIds);
        }

        $sent = 0;

        foreach ($query->cursor() as $invite) {
            $this->send($invite);
            $sent++;
        }

        return ['sent' => $sent];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{created: int, skipped: int, errors: list<array{row: int, message: string}>}
     */
    public function importRows(Event $event, array $rows): array
    {
        $created = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($event, $rows, &$created, &$skipped, &$errors): void {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                if (empty($row['email'])) {
                    $errors[] = ['row' => $rowNumber, 'message' => 'Email is required.'];
                    $skipped++;

                    continue;
                }

                if (! filter_var((string) $row['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = ['row' => $rowNumber, 'message' => 'Invalid email address.'];
                    $skipped++;

                    continue;
                }

                $exists = GuestInvite::query()
                    ->where('event_id', $event->id)
                    ->where('email', strtolower((string) $row['email']))
                    ->exists();

                if ($exists) {
                    $errors[] = ['row' => $rowNumber, 'message' => 'Guest with this email already exists for the event.'];
                    $skipped++;

                    continue;
                }

                $tags = [];
                if (! empty($row['tags'])) {
                    $tags = array_values(array_filter(array_map('trim', explode(',', (string) $row['tags']))));
                }

                $tableId = null;
                if (! empty($row['table'])) {
                    $table = $event->eventTables()->where('name', (string) $row['table'])->first();
                    $tableId = $table?->id;
                }

                $this->create($event, [
                    'email' => $row['email'],
                    'first_name' => $row['first_name'] ?? 'Guest',
                    'last_name' => $row['last_name'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'household_name' => $row['household_name'] ?? null,
                    'group_label' => $row['group_label'] ?? null,
                    'tags' => $tags,
                    'plus_one_limit' => isset($row['plus_one_limit']) && $row['plus_one_limit'] !== ''
                        ? (int) $row['plus_one_limit']
                        : null,
                    'table_id' => $tableId,
                ]);

                $created++;
            }
        });

        $this->metricsService->invalidateForEvent($event);

        return compact('created', 'skipped', 'errors');
    }
}
