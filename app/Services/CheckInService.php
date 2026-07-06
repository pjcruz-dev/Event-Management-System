<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\CheckIn\BatchSyncItemResult;
use App\DTO\CheckIn\CheckInScanResult;
use App\Enums\OrderStatus;
use App\Enums\RegistrationStatus;
use App\Events\RegistrationCheckedIn;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class CheckInService
{
    public function __construct(
        private readonly QrTokenService $qrTokenService,
    ) {}

    /**
     * @param  array{
     *   device_id?: string|null,
     *   gate?: string|null,
     *   latitude?: float|null,
     *   longitude?: float|null,
     * }  $context
     */
    public function scan(
        Event $event,
        string $token,
        User $staff,
        array $context = [],
    ): CheckInScanResult {
        $registration = $this->qrTokenService->verify($token);

        if ($registration === null || $registration->event_id !== $event->id) {
            $this->logAttempt($event, $staff, null, 'checkin.scan.rejected', [
                'reason' => 'invalid_token',
            ]);

            return new CheckInScanResult('rejected', 'Invalid or unrecognized ticket.');
        }

        return $this->processCheckIn($registration, $staff, $context, $token);
    }

    /**
     * @param  array<int, array{
     *   token: string,
     *   idempotency_key: string,
     *   scanned_at: string,
     *   device_id?: string|null,
     *   gate?: string|null,
     *   latitude?: float|null,
     *   longitude?: float|null,
     * }>  $items
     * @return list<BatchSyncItemResult>
     */
    public function syncBatch(Event $event, User $staff, array $items): array
    {
        usort($items, fn (array $a, array $b): int => strcmp($a['scanned_at'], $b['scanned_at']));

        $results = [];

        foreach ($items as $item) {
            $registration = $this->qrTokenService->verify($item['token']);

            if ($registration === null || $registration->event_id !== $event->id) {
                $this->logAttempt($event, $staff, null, 'checkin.scan.rejected', [
                    'reason' => 'invalid_token',
                    'idempotency_key' => $item['idempotency_key'],
                ]);

                $results[] = new BatchSyncItemResult(
                    idempotencyKey: $item['idempotency_key'],
                    status: 'rejected',
                    reason: 'Invalid or unrecognized ticket.',
                );

                continue;
            }

            $context = [
                'device_id' => $item['device_id'] ?? null,
                'gate' => $item['gate'] ?? null,
                'latitude' => $item['latitude'] ?? null,
                'longitude' => $item['longitude'] ?? null,
                'scanned_at' => $item['scanned_at'],
            ];

            $scan = $this->processCheckIn($registration, $staff, $context, $item['token'], $item['idempotency_key']);

            $results[] = new BatchSyncItemResult(
                idempotencyKey: $item['idempotency_key'],
                status: $scan->status,
                reason: $scan->reason,
                duplicateInfo: $scan->duplicateInfo,
            );
        }

        return $results;
    }

    /**
     * @param  array{gate?: string|null}  $context
     */
    public function manualCheckIn(
        Registration $registration,
        User $staff,
        array $context = [],
    ): CheckInScanResult {
        return $this->processCheckIn($registration, $staff, [
            'gate' => $context['gate'] ?? null,
        ], 'manual');
    }

    /**
     * @param  array{
     *   device_id?: string|null,
     *   gate?: string|null,
     *   latitude?: float|null,
     *   longitude?: float|null,
     *   scanned_at?: string|null,
     * }  $context
     */
    private function processCheckIn(
        Registration $registration,
        User $staff,
        array $context,
        string $token,
        ?string $idempotencyKey = null,
    ): CheckInScanResult {
        $registration->loadMissing(['order', 'ticketType', 'checkedInBy']);

        if ($registration->status === RegistrationStatus::Cancelled
            || $registration->status === RegistrationStatus::Waitlisted) {
            $this->logAttempt($registration->event, $staff, $registration, 'checkin.scan.rejected', [
                'reason' => 'invalid_registration_status',
                'idempotency_key' => $idempotencyKey,
            ]);

            return new CheckInScanResult('rejected', 'This registration is not eligible for check-in.');
        }

        $hasOrder = $registration->order !== null;
        $orderPaid = $hasOrder && $registration->order->status === OrderStatus::Paid;
        $isOrderlessConfirmation = ! $hasOrder && $registration->status === RegistrationStatus::Confirmed;

        if ($hasOrder && ! $orderPaid) {
            $this->logAttempt($registration->event, $staff, $registration, 'checkin.scan.rejected', [
                'reason' => 'unpaid_order',
                'idempotency_key' => $idempotencyKey,
            ]);

            return new CheckInScanResult('rejected', 'Payment has not been completed for this registration.');
        }

        if (! $orderPaid && ! $isOrderlessConfirmation) {
            $this->logAttempt($registration->event, $staff, $registration, 'checkin.scan.rejected', [
                'reason' => 'not_confirmed',
                'idempotency_key' => $idempotencyKey,
            ]);

            return new CheckInScanResult('rejected', 'This registration has not been confirmed.');
        }

        if ($registration->checked_in_at !== null) {
            $duplicateInfo = [
                'checked_in_at' => $registration->checked_in_at->toIso8601String(),
                'checked_in_by' => $registration->checkedInBy?->name,
                'gate' => $registration->check_in_gate,
            ];

            $this->logAttempt($registration->event, $staff, $registration, 'checkin.scan.duplicate', [
                'idempotency_key' => $idempotencyKey,
                'duplicate_info' => $duplicateInfo,
            ]);

            return new CheckInScanResult(
                status: 'duplicate',
                reason: 'Attendee is already checked in.',
                registration: $registration,
                duplicateInfo: $duplicateInfo,
                isVip: $this->isVip($registration),
            );
        }

        $checkedInAt = isset($context['scanned_at'])
            ? Carbon::parse($context['scanned_at'])
            : now();

        DB::transaction(function () use ($registration, $staff, $context, $checkedInAt): void {
            $locked = Registration::query()->whereKey($registration->id)->lockForUpdate()->first();

            if ($locked === null || $locked->checked_in_at !== null) {
                return;
            }

            $locked->forceFill([
                'checked_in_at' => $checkedInAt,
                'checked_in_by' => $staff->id,
                'check_in_device_id' => $context['device_id'] ?? null,
                'check_in_gate' => $context['gate'] ?? null,
                'check_in_latitude' => $context['latitude'] ?? null,
                'check_in_longitude' => $context['longitude'] ?? null,
            ])->save();
        });

        $registration->refresh()->load(['ticketType', 'checkedInBy']);

        if ($registration->checked_in_at === null) {
            return $this->buildDuplicateAfterRace($registration, $staff, $idempotencyKey);
        }

        $this->logAttempt($registration->event, $staff, $registration, 'checkin.scan.success', [
            'idempotency_key' => $idempotencyKey,
            'gate' => $context['gate'] ?? null,
        ]);

        event(new RegistrationCheckedIn($registration, $staff));

        return new CheckInScanResult(
            status: 'success',
            registration: $registration,
            isVip: $this->isVip($registration),
        );
    }

    private function buildDuplicateAfterRace(
        Registration $registration,
        User $staff,
        ?string $idempotencyKey,
    ): CheckInScanResult {
        $registration->refresh()->load(['checkedInBy']);

        $duplicateInfo = [
            'checked_in_at' => $registration->checked_in_at?->toIso8601String() ?? now()->toIso8601String(),
            'checked_in_by' => $registration->checkedInBy?->name,
            'gate' => $registration->check_in_gate,
        ];

        $this->logAttempt($registration->event, $staff, $registration, 'checkin.scan.duplicate', [
            'idempotency_key' => $idempotencyKey,
            'duplicate_info' => $duplicateInfo,
        ]);

        return new CheckInScanResult(
            status: 'duplicate',
            reason: 'Attendee is already checked in.',
            registration: $registration,
            duplicateInfo: $duplicateInfo,
            isVip: $this->isVip($registration),
        );
    }

    private function isVip(Registration $registration): bool
    {
        $tag = $registration->ticketType?->type_tag;

        return $tag !== null && strcasecmp($tag, 'VIP') === 0;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function logAttempt(
        Event $event,
        User $staff,
        ?Registration $registration,
        string $action,
        array $metadata,
    ): void {
        ActivityLog::query()->create([
            'organization_id' => $event->organization_id,
            'actor_id' => $staff->id,
            'action' => $action,
            'subject_type' => $registration !== null ? Registration::class : null,
            'subject_id' => $registration?->id,
            'metadata' => array_merge($metadata, [
                'event_id' => $event->id,
            ]),
        ]);
    }
}
