<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\EventTable;
use App\Models\GuestInvite;
use App\Models\Registration;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SeatingAssignmentService
{
    /**
     * @param  array{
     *   registrations?: list<array{id: int, table_id: int|null}>,
     *   guest_invites?: list<array{id: int, table_id: int|null}>,
     * }  $assignments
     */
    public function bulkAssign(Event $event, array $assignments): void
    {
        $tables = $event->eventTables()->get()->keyBy('id');

        DB::transaction(function () use ($event, $assignments, $tables): void {
            $occupancy = [];

            foreach ($tables as $table) {
                $occupancy[$table->id] = Registration::query()
                    ->where('event_id', $event->id)
                    ->where('table_id', $table->id)
                    ->count()
                    + GuestInvite::query()
                        ->where('event_id', $event->id)
                        ->where('table_id', $table->id)
                        ->whereNull('registration_id')
                        ->count();
            }

            foreach ($assignments['registrations'] ?? [] as $item) {
                $this->assignRegistration($event, $tables, $occupancy, (int) $item['id'], $item['table_id'] !== null ? (int) $item['table_id'] : null);
            }

            foreach ($assignments['guest_invites'] ?? [] as $item) {
                $this->assignGuestInvite($event, $tables, $occupancy, (int) $item['id'], $item['table_id'] !== null ? (int) $item['table_id'] : null);
            }
        });
    }

    /**
     * @param  \Illuminate\Support\Collection<int, EventTable>  $tables
     * @param  array<int, int>  $occupancy
     */
    private function assignRegistration(
        Event $event,
        \Illuminate\Support\Collection $tables,
        array &$occupancy,
        int $registrationId,
        ?int $tableId,
    ): void {
        $registration = Registration::query()
            ->where('event_id', $event->id)
            ->whereKey($registrationId)
            ->firstOrFail();

        $previousTableId = $registration->table_id;

        if ($previousTableId !== null && isset($occupancy[$previousTableId])) {
            $occupancy[$previousTableId] = max(0, $occupancy[$previousTableId] - 1);
        }

        if ($tableId === null) {
            $registration->update(['table_id' => null]);

            return;
        }

        $table = $tables->get($tableId);
        if ($table === null) {
            throw ValidationException::withMessages([
                'table_id' => ["Table [{$tableId}] does not belong to this event."],
            ]);
        }

        if (($occupancy[$tableId] ?? 0) >= $table->capacity) {
            throw ValidationException::withMessages([
                'table_id' => ["Table \"{$table->name}\" is at capacity."],
            ]);
        }

        $registration->update(['table_id' => $tableId]);
        $occupancy[$tableId] = ($occupancy[$tableId] ?? 0) + 1;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, EventTable>  $tables
     * @param  array<int, int>  $occupancy
     */
    private function assignGuestInvite(
        Event $event,
        \Illuminate\Support\Collection $tables,
        array &$occupancy,
        int $inviteId,
        ?int $tableId,
    ): void {
        $invite = GuestInvite::query()
            ->where('event_id', $event->id)
            ->whereKey($inviteId)
            ->firstOrFail();

        $previousTableId = $invite->table_id;

        if ($previousTableId !== null && isset($occupancy[$previousTableId]) && $invite->registration_id === null) {
            $occupancy[$previousTableId] = max(0, $occupancy[$previousTableId] - 1);
        }

        if ($tableId === null) {
            $invite->update(['table_id' => null]);

            return;
        }

        $table = $tables->get($tableId);
        if ($table === null) {
            throw ValidationException::withMessages([
                'table_id' => ["Table [{$tableId}] does not belong to this event."],
            ]);
        }

        if ($invite->registration_id === null && ($occupancy[$tableId] ?? 0) >= $table->capacity) {
            throw ValidationException::withMessages([
                'table_id' => ["Table \"{$table->name}\" is at capacity."],
            ]);
        }

        $invite->update(['table_id' => $tableId]);

        if ($invite->registration_id === null) {
            $occupancy[$tableId] = ($occupancy[$tableId] ?? 0) + 1;
        }
    }
}
