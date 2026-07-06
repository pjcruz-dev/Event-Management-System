<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registration\UpdateRegistrationFormRequest;
use App\Http\Resources\RegistrationFormResource;
use App\Models\Event;
use App\Models\RegistrationForm;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class RegistrationFormController extends Controller
{
    public function show(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $form = $event->registrationForm ?? RegistrationForm::query()->create([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'fields' => [
                [
                    'key' => 'company',
                    'type' => 'text',
                    'label' => 'Company',
                    'required' => false,
                    'options' => [],
                ],
            ],
        ]);

        return ApiResponse::success(new RegistrationFormResource($form));
    }

    public function update(UpdateRegistrationFormRequest $request, Event $event): JsonResponse
    {
        $form = $event->registrationForm ?? RegistrationForm::query()->create([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'fields' => [],
        ]);

        $this->authorize('update', $form);

        $form->update($request->validated());

        return ApiResponse::success(
            new RegistrationFormResource($form->fresh()),
            'Registration form updated successfully.',
        );
    }
}
