<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExhibitorLeadResource;
use App\Http\Resources\ExhibitorResource;
use App\Models\ExhibitorContact;
use App\Services\ExhibitorLeadService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class ExhibitorPortalAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $contact = ExhibitorContact::query()
            ->where('email', $data['email'])
            ->first();

        if ($contact === null || ! Hash::check($data['password'], $contact->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $token = $contact->createToken('exhibitor-portal', ['exhibitor-portal'])->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'exhibitor_id' => $contact->exhibitor_id,
                'event_id' => $contact->event_id,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $contact = $request->user();
        if ($contact instanceof ExhibitorContact) {
            $contact->currentAccessToken()?->delete();
        }

        return ApiResponse::success(message: 'Logged out.');
    }
}
