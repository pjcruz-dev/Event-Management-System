<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EventSession;
use Illuminate\Support\Collection;

final class SessionConflictService
{
    /**
     * @return list<array{type: string, message: string, session_id: int, speaker_id?: int, room?: string}>
     */
    public function detect(EventSession $session, ?int $excludeSessionId = null): array
    {
        $warnings = [];

        if ($session->room) {
            $roomConflicts = EventSession::query()
                ->where('event_id', $session->event_id)
                ->where('room', $session->room)
                ->where('id', '!=', $excludeSessionId ?? $session->id)
                ->where('starts_at', '<', $session->ends_at)
                ->where('ends_at', '>', $session->starts_at)
                ->get();

            foreach ($roomConflicts as $conflict) {
                $warnings[] = [
                    'type' => 'room_conflict',
                    'message' => "Room {$session->room} is also booked for \"{$conflict->title}\".",
                    'session_id' => $conflict->id,
                    'room' => $session->room,
                ];
            }
        }

        $session->loadMissing('speakers');
        $speakerIds = $session->speakers->pluck('id');

        if ($speakerIds->isEmpty()) {
            return $warnings;
        }

        $overlappingSessions = EventSession::query()
            ->where('event_id', $session->event_id)
            ->where('id', '!=', $excludeSessionId ?? $session->id)
            ->where('starts_at', '<', $session->ends_at)
            ->where('ends_at', '>', $session->starts_at)
            ->whereHas('speakers', fn ($query) => $query->whereIn('speakers.id', $speakerIds))
            ->with('speakers')
            ->get();

        foreach ($overlappingSessions as $overlap) {
            foreach ($overlap->speakers as $speaker) {
                if (! $speakerIds->contains($speaker->id)) {
                    continue;
                }

                $warnings[] = [
                    'type' => 'speaker_overlap',
                    'message' => "{$speaker->name} is also assigned to \"{$overlap->title}\" at an overlapping time.",
                    'session_id' => $overlap->id,
                    'speaker_id' => $speaker->id,
                ];
            }
        }

        return $warnings;
    }

    /**
     * @param  Collection<int, EventSession>  $sessions
     * @return list<array{type: string, message: string, session_id: int, speaker_id?: int, room?: string}>
     */
    public function detectForCollection(Collection $sessions): array
    {
        $warnings = [];

        foreach ($sessions as $session) {
            $warnings = [...$warnings, ...$this->detect($session, $session->id)];
        }

        return $warnings;
    }
}
