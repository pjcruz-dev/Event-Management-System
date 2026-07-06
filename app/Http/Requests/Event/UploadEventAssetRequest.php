<?php

declare(strict_types=1);

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UploadEventAssetRequest extends FormRequest
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
        $type = $this->input('type');
        $isVideo = $type === 'hero_video';

        return [
            'type' => ['required', Rule::in(['logo', 'hero', 'hero_video', 'og_image'])],
            'file' => [
                'required',
                'file',
                $isVideo ? 'mimes:mp4,webm' : 'mimes:jpg,jpeg,png,webp,svg',
                $isVideo ? 'max:51200' : 'max:5120',
            ],
        ];
    }
}
