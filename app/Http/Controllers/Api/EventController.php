<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Event\ArchiveEventAction;
use App\Actions\Event\DuplicateEventAction;
use App\Actions\Event\PublishEventAction;
use App\Enums\CustomDomainVerificationStatus;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Event\DeleteEventAssetRequest;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventBuilderRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Requests\Event\UploadEventAssetRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\EventSlugService;
use App\Services\LandingPageConfigSanitizer;
use App\Services\StorageService;
use App\Services\TenantContext;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Event::class);

        $query = Event::query()->orderByDesc('starts_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('venue', 'like', "%{$search}%");
            });
        }

        if ($request->filled('starts_from')) {
            $query->whereDate('starts_at', '>=', $request->string('starts_from')->toString());
        }

        if ($request->filled('starts_to')) {
            $query->whereDate('starts_at', '<=', $request->string('starts_to')->toString());
        }

        $paginator = $query->paginate(
            perPage: min($request->integer('per_page', 15), 50),
        );

        return ApiResponse::paginated($paginator->through(
            fn (Event $event): EventResource => new EventResource($event),
        ));
    }

    public function store(
        StoreEventRequest $request,
        EventSlugService $slugService,
        TenantContext $tenantContext,
    ): JsonResponse {
        $this->authorize('create', Event::class);

        $organization = $tenantContext->get();
        abort_if($organization === null, 422, 'Organization context is required.');

        $slug = $slugService->generateUnique(
            $organization,
            $request->validated('name'),
            $request->validated('slug'),
        );

        $event = Event::query()->create([
            ...$request->safe()->except(['slug']),
            'slug' => $slug,
            'status' => EventStatus::Draft,
            'custom_domain_verification_status' => CustomDomainVerificationStatus::Unverified,
            'theme_config' => $request->validated('theme_config') ?? $this->defaultThemeConfig(),
            'landing_page_config' => $request->validated('landing_page_config') ?? $this->defaultLandingPageConfig(),
        ]);

        return ApiResponse::created(
            new EventResource($event),
            'Event created successfully.',
        );
    }

    public function show(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        return ApiResponse::success(new EventResource($event));
    }

    public function update(
        UpdateEventRequest $request,
        Event $event,
        EventSlugService $slugService,
    ): JsonResponse {
        $this->authorize('update', $event);

        $data = $request->validated();

        if (array_key_exists('slug', $data) && $data['slug'] !== null) {
            $organization = $event->organization;
            $data['slug'] = $slugService->generateUnique(
                $organization,
                $data['name'] ?? $event->name,
                $data['slug'],
            );
        }

        if (array_key_exists('custom_domain', $data) && $data['custom_domain'] !== $event->custom_domain) {
            $data['custom_domain_verification_status'] = CustomDomainVerificationStatus::Unverified;
        }

        $event->update($data);

        return ApiResponse::success(
            new EventResource($event->fresh()),
            'Event updated successfully.',
        );
    }

    public function destroy(Event $event): JsonResponse
    {
        $this->authorize('delete', $event);
        $event->delete();

        return ApiResponse::success(message: 'Event deleted successfully.');
    }

    public function publish(Event $event, PublishEventAction $action): JsonResponse
    {
        $this->authorize('publish', $event);

        return ApiResponse::success(
            new EventResource($action->handle($event)),
            'Event published successfully.',
        );
    }

    public function archive(Event $event, ArchiveEventAction $action): JsonResponse
    {
        $this->authorize('archive', $event);

        return ApiResponse::success(
            new EventResource($action->handle($event)),
            'Event archived successfully.',
        );
    }

    public function duplicate(Event $event, DuplicateEventAction $action): JsonResponse
    {
        $this->authorize('duplicate', $event);

        return ApiResponse::created(
            new EventResource($action->handle($event)),
            'Event duplicated successfully.',
        );
    }

    public function updateBuilder(
        UpdateEventBuilderRequest $request,
        Event $event,
        StorageService $storage,
        LandingPageConfigSanitizer $landingPageConfigSanitizer,
    ): JsonResponse {
        $this->authorize('update', $event);

        $data = $request->validated();
        $data['theme_config'] = $storage->normalizeThemeConfigPaths($data['theme_config']);
        $data['landing_page_config'] = $landingPageConfigSanitizer->sanitize($data['landing_page_config']);
        $event->update($data);

        return ApiResponse::success(
            new EventResource($event->fresh()),
            'Event builder saved successfully.',
        );
    }

    public function uploadAsset(
        UploadEventAssetRequest $request,
        Event $event,
        StorageService $storage,
    ): JsonResponse {
        $this->authorize('update', $event);

        $type = $request->validated('type');
        $directory = "events/{$event->id}/assets";
        $path = $storage->store($request->file('file'), $directory, 'public');

        if ($type === 'og_image') {
            if ($event->og_image_path !== null) {
                $storage->delete($event->og_image_path, 'public');
            }

            $event->update(['og_image_path' => $path]);
        } else {
            $themeConfig = $event->theme_config ?? $this->defaultThemeConfig();
            $configKey = match ($type) {
                'logo' => 'logo_url',
                'hero_video' => 'hero_video_url',
                default => 'hero_image_url',
            };

            if (isset($themeConfig[$configKey]) && is_string($themeConfig[$configKey])) {
                $storage->delete($themeConfig[$configKey], 'public');
            }

            $themeConfig[$configKey] = $path;

            if ($type === 'hero_video') {
                $themeConfig['hero_background_type'] = 'video';
            }

            $event->update(['theme_config' => $themeConfig]);
        }

        return ApiResponse::success(
            new EventResource($event->fresh()),
            'Asset uploaded successfully.',
        );
    }

    public function deleteAsset(
        DeleteEventAssetRequest $request,
        Event $event,
        StorageService $storage,
    ): JsonResponse {
        $this->authorize('update', $event);

        $type = $request->validated('type');
        $themeConfig = $event->theme_config ?? $this->defaultThemeConfig();
        $configKey = match ($type) {
            'logo' => 'logo_url',
            'hero_video' => 'hero_video_url',
            default => 'hero_image_url',
        };

        if (isset($themeConfig[$configKey]) && is_string($themeConfig[$configKey]) && $themeConfig[$configKey] !== '') {
            $storage->delete($themeConfig[$configKey], 'public');
        }

        $themeConfig[$configKey] = null;

        if ($type === 'hero_video') {
            $themeConfig['hero_background_type'] = 'image';
        }

        $event->update(['theme_config' => $themeConfig]);

        return ApiResponse::success(
            new EventResource($event->fresh()),
            'Asset removed successfully.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultThemeConfig(): array
    {
        return [
            'primary_color' => '#1E40AF',
            'secondary_color' => '#F59E0B',
            'font' => 'Inter',
            'logo_url' => null,
            'hero_image_url' => null,
            'hero_video_url' => null,
            'hero_background_type' => 'image',
            'layout_variant' => 'classic',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultLandingPageConfig(): array
    {
        return [
            'blocks' => [
                ['type' => 'hero', 'settings' => ['headline' => '', 'subheadline' => '']],
                ['type' => 'about', 'settings' => ['body' => '']],
                ['type' => 'cta', 'settings' => ['button_label' => 'Register now']],
            ],
        ];
    }
}
