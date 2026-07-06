<?php

declare(strict_types=1);

namespace App\Http\Requests\Registration;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;
use App\Models\RegistrationForm;
use App\Services\RegistrationFormValidator;
use Illuminate\Foundation\Http\FormRequest;

final class PublicRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'ticket_type_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'attendee_first_name' => ['required', 'string', 'max:255'],
            'attendee_last_name' => ['required', 'string', 'max:255'],
            'attendee_email' => ['required', 'email', 'max:255'],
            'attendee_phone' => ['nullable', 'string', 'max:50'],
            'custom_fields' => ['nullable', 'array'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ];

        $event = $this->resolvedEvent();
        $form = RegistrationForm::withoutTenantScope('public registration validation')
            ->where('event_id', $event->id)
            ->first();

        if ($form !== null) {
            $rules = array_merge($rules, app(RegistrationFormValidator::class)->rulesFor($form));
        }

        return $rules;
    }

    public function resolvedEvent(): Event
    {
        $slug = (string) $this->route('slug');

        $event = Event::withoutTenantScope('public registration request')
            ->where('slug', $slug)
            ->where('status', EventStatus::Published)
            ->where('visibility', EventVisibility::Public)
            ->first();

        if ($event === null) {
            abort(404);
        }

        return $event;
    }
}
