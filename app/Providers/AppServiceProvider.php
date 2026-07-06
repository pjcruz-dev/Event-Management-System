<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\CacheService;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\StripeGateway;
use App\Services\TenantContext;
use App\Services\OrganizationRoleService;
use App\Services\StorageService;
use App\Events\OrderPaid as OrderPaidEvent;
use App\Events\RegistrationCheckedIn as RegistrationCheckedInEvent;
use App\Events\RegistrationCancelled as RegistrationCancelledEvent;
use App\Events\RegistrationConfirmed as RegistrationConfirmedEvent;
use App\Listeners\GenerateRegistrationQrToken;
use App\Listeners\InvalidateDashboardMetricsCache;
use App\Listeners\PromoteNextWaitingListEntry;
use App\Listeners\SendOrderTicketsNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheService::class);
        $this->app->singleton(StorageService::class);
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(OrganizationRoleService::class);
        $this->app->bind(PaymentGatewayInterface::class, StripeGateway::class);
    }

    public function boot(): void
    {
        Event::listen(RegistrationConfirmedEvent::class, GenerateRegistrationQrToken::class);
        Event::listen(OrderPaidEvent::class, SendOrderTicketsNotification::class);
        Event::listen(OrderPaidEvent::class, [InvalidateDashboardMetricsCache::class, 'handle']);
        Event::listen(RegistrationConfirmedEvent::class, [InvalidateDashboardMetricsCache::class, 'handle']);
        Event::listen(RegistrationCheckedInEvent::class, [InvalidateDashboardMetricsCache::class, 'handle']);
        Event::listen(RegistrationCancelledEvent::class, PromoteNextWaitingListEntry::class);
        Event::listen(RegistrationCancelledEvent::class, [InvalidateDashboardMetricsCache::class, 'handle']);

        \Illuminate\Support\Facades\Route::bind('session', fn (string $value) => \App\Models\EventSession::withoutTenantScope('route binding')->findOrFail($value));

        ResetPassword::createUrlUsing(
            function (object $notifiable, string $token): string {
                $frontend = rtrim((string) config('app.frontend_url'), '/');

                return $frontend.'/reset-password?'.http_build_query([
                    'token' => $token,
                    'email' => $notifiable->getEmailForVerification(),
                ]);
            },
        );
    }
}
