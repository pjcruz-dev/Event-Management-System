<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExhibitorLeadResource;
use App\Http\Resources\ExhibitorResource;
use App\Models\ExhibitorContact;
use App\Models\EventSession;
use App\Models\SessionRegistration;
use App\Services\ExhibitorLeadService;
use App\Services\TenantContext;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ExhibitorPortalController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        /** @var ExhibitorContact $contact */
        $contact = $request->user();
        $contact->load('exhibitor.booth');

        return ApiResponse::success([
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
            ],
            'exhibitor' => new ExhibitorResource($contact->exhibitor),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var ExhibitorContact $contact */
        $contact = $request->user();

        $data = $request->validate([
            'description' => ['nullable', 'string'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'materials' => ['nullable', 'array'],
            'materials.*.name' => ['required', 'string', 'max:255'],
            'materials.*.url' => ['required', 'url', 'max:500'],
        ]);

        $contact->exhibitor->update($data);

        return ApiResponse::success(new ExhibitorResource($contact->exhibitor->fresh()->load('booth')));
    }

    public function leads(Request $request, TenantContext $tenantContext): JsonResponse
    {
        /** @var ExhibitorContact $contact */
        $contact = $request->user();
        $tenantContext->set($contact->organization);

        $leads = $contact->exhibitor->leads()
            ->with('registration')
            ->latest('scanned_at')
            ->get();

        return ApiResponse::success(ExhibitorLeadResource::collection($leads));
    }

    public function scanLead(Request $request, ExhibitorLeadService $leadService): JsonResponse
    {
        /** @var ExhibitorContact $contact */
        $contact = $request->user();

        $data = $request->validate([
            'token' => ['required', 'string', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $lead = $leadService->capture($contact, $data['token'], $data['notes'] ?? null);
        $lead->load('registration');

        return ApiResponse::created(new ExhibitorLeadResource($lead), 'Lead captured.');
    }
}
