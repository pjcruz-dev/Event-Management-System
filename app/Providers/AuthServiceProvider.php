<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Coupon;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\EventTable;
use App\Models\Exhibitor;
use App\Models\GuestInvite;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\RegistrationForm;
use App\Models\Speaker;
use App\Models\Sponsor;
use App\Models\TenantFixture;
use App\Models\TicketType;
use App\Models\Track;
use App\Policies\CouponPolicy;
use App\Policies\EventPolicy;
use App\Policies\EventSessionPolicy;
use App\Policies\EventTablePolicy;
use App\Policies\ExhibitorPolicy;
use App\Policies\GuestInvitePolicy;
use App\Policies\InvitationPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\RegistrationFormPolicy;
use App\Policies\SpeakerPolicy;
use App\Policies\SponsorPolicy;
use App\Policies\TenantFixturePolicy;
use App\Policies\TicketTypePolicy;
use App\Policies\TrackPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(TicketType::class, TicketTypePolicy::class);
        Gate::policy(Track::class, TrackPolicy::class);
        Gate::policy(EventSession::class, EventSessionPolicy::class);
        Gate::policy(GuestInvite::class, GuestInvitePolicy::class);
        Gate::policy(EventTable::class, EventTablePolicy::class);
        Gate::policy(Speaker::class, SpeakerPolicy::class);
        Gate::policy(Sponsor::class, SponsorPolicy::class);
        Gate::policy(Exhibitor::class, ExhibitorPolicy::class);
        Gate::policy(RegistrationForm::class, RegistrationFormPolicy::class);
        Gate::policy(Coupon::class, CouponPolicy::class);
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Invitation::class, InvitationPolicy::class);
        Gate::policy(TenantFixture::class, TenantFixturePolicy::class);
    }
}
