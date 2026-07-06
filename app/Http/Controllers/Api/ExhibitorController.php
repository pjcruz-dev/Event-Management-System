<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExhibitorResource;
use App\Models\Event;
use App\Models\Exhibitor;
use App\Models\ExhibitorContact;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class ExhibitorController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $exhibitors = $event->exhibitors()
            ->with('booth')
            ->withCount('leads')
            ->orderBy('name')
            ->get();

        return ApiResponse::success(ExhibitorResource::collection($exhibitors));
    }

    public function store(Request $request, Event $event): JsonResponse
    {
        $this->authorize('create', Exhibitor::class);
        $this->authorize('update', $event);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo_path' => ['nullable', 'string', 'max:500'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'booth_code' => ['nullable', 'string', 'max:50'],
            'booth_location' => ['nullable', 'string', 'max:255'],
        ]);

        $exhibitor = $event->exhibitors()->create([
            'organization_id' => $event->organization_id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'logo_path' => $data['logo_path'] ?? null,
            'website_url' => $data['website_url'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
        ]);

        if (! empty($data['booth_code'])) {
            $exhibitor->booths()->create([
                'organization_id' => $event->organization_id,
                'event_id' => $event->id,
                'code' => $data['booth_code'],
                'location' => $data['booth_location'] ?? null,
            ]);
        }

        return ApiResponse::created(new ExhibitorResource($exhibitor->load('booth')));
    }

    public function update(Request $request, Event $event, Exhibitor $exhibitor): JsonResponse
    {
        $this->authorize('update', $exhibitor);
        abort_unless($exhibitor->event_id === $event->id, 404);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo_path' => ['nullable', 'string', 'max:500'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ]);

        $exhibitor->update($data);

        return ApiResponse::success(new ExhibitorResource($exhibitor->fresh()->load('booth')));
    }

    public function destroy(Event $event, Exhibitor $exhibitor): JsonResponse
    {
        $this->authorize('delete', $exhibitor);
        abort_unless($exhibitor->event_id === $event->id, 404);
        $exhibitor->delete();

        return ApiResponse::success(message: 'Exhibitor deleted.');
    }

    public function inviteContact(Request $request, Event $event, Exhibitor $exhibitor): JsonResponse
    {
        $this->authorize('update', $exhibitor);
        abort_unless($exhibitor->event_id === $event->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $password = $data['password'] ?? Str::password(12);

        $contact = ExhibitorContact::query()->updateOrCreate(
            [
                'exhibitor_id' => $exhibitor->id,
                'email' => $data['email'],
            ],
            [
                'organization_id' => $event->organization_id,
                'event_id' => $event->id,
                'name' => $data['name'],
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        return ApiResponse::created([
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'temporary_password' => $data['password'] ? null : $password,
        ], 'Exhibitor contact invited.');
    }
}
