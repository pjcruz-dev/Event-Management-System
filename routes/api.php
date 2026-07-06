<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\ConferenceAnalyticsController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventSeatingController;
use App\Http\Controllers\Api\EventSessionController;
use App\Http\Controllers\Api\EventTableController;
use App\Http\Controllers\Api\ExhibitorController;
use App\Http\Controllers\Api\ExhibitorPortalAuthController;
use App\Http\Controllers\Api\ExhibitorPortalController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\EventPreviewTokenController;
use App\Http\Controllers\Api\EventDashboardController;
use App\Http\Controllers\Api\OrganizationDashboardController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\DiscoverController;
use App\Http\Controllers\Api\PublicOrganizationController;
use App\Http\Controllers\Api\PublicReviewController;
use App\Http\Controllers\Api\PublicConferenceController;
use App\Http\Controllers\Api\PublicEventController;
use App\Http\Controllers\Api\PublicEventPreviewController;
use App\Http\Controllers\Api\PublicEventRegistrationController;
use App\Http\Controllers\Api\PublicRsvpController;
use App\Http\Controllers\Api\GuestInviteController;
use App\Http\Controllers\Api\RegistrationFormController;
use App\Http\Controllers\Api\SpeakerController;
use App\Http\Controllers\Api\SponsorController;
use App\Http\Controllers\Api\TicketTypeController;
use App\Http\Controllers\Api\TrackController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\TenantFixtureController;
use App\Http\Controllers\Api\WalkInController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', [HealthController::class, 'show']);

    Route::post('/webhooks/stripe', [PaymentController::class, 'handleWebhook'])
        ->middleware(\App\Http\Middleware\VerifyStripeWebhook::class);

    Route::prefix('discover')->group(function (): void {
        Route::get('/events', [DiscoverController::class, 'index']);
        Route::get('/events/sitemap', [DiscoverController::class, 'sitemap']);
    });

    Route::prefix('public')->group(function (): void {
        Route::get('/events', [PublicEventController::class, 'index']);
        Route::get('/events/{slug}', [PublicEventRegistrationController::class, 'show']);
        Route::get('/events/{slug}/preview', [PublicEventPreviewController::class, 'show']);
        Route::get('/events/{slug}/registration-form', [PublicEventRegistrationController::class, 'registrationForm']);
        Route::post('/events/{slug}/apply-coupon', [PublicEventRegistrationController::class, 'applyCoupon'])
            ->middleware('throttle:20,1');
        Route::post('/events/{slug}/register', [PublicEventRegistrationController::class, 'register'])
            ->middleware('throttle:20,1');
        Route::get('/events/{slug}/agenda', [PublicConferenceController::class, 'agenda']);
        Route::get('/events/{slug}/speakers', [PublicConferenceController::class, 'speakers']);
        Route::get('/events/{slug}/sponsors', [PublicConferenceController::class, 'sponsors']);
        Route::get('/events/{slug}/exhibitors', [PublicConferenceController::class, 'exhibitors']);
        Route::get('/events/{slug}/speakers/{speaker}', [PublicConferenceController::class, 'speaker']);
        Route::post('/events/{slug}/sessions/{session}/register', [PublicConferenceController::class, 'registerForSession'])
            ->middleware('throttle:20,1');
        Route::get('/events/{slug}/reviews', [PublicReviewController::class, 'index']);
        Route::post('/events/{slug}/reviews', [PublicReviewController::class, 'store'])
            ->middleware('throttle:10,1');
        Route::get('/events/{slug}/similar', [PublicReviewController::class, 'similar']);
        Route::get('/organizations/{slug}', [PublicOrganizationController::class, 'show']);

        Route::get('/rsvp/{token}', [PublicRsvpController::class, 'show']);
        Route::post('/rsvp/{token}/respond', [PublicRsvpController::class, 'respond'])
            ->middleware('throttle:30,1');

        Route::post('/orders/{orderNumber}/checkout', [PaymentController::class, 'publicCheckout'])
            ->middleware('throttle:10,1');
    });

    Route::prefix('exhibitor-portal')->group(function (): void {
        Route::post('/auth/login', [ExhibitorPortalAuthController::class, 'login'])
            ->middleware('throttle:10,1');

        Route::middleware(['auth:sanctum', 'exhibitor.contact'])->group(function (): void {
            Route::post('/auth/logout', [ExhibitorPortalAuthController::class, 'logout']);
            Route::get('/me', [ExhibitorPortalController::class, 'me']);
            Route::put('/profile', [ExhibitorPortalController::class, 'updateProfile']);
            Route::get('/leads', [ExhibitorPortalController::class, 'leads']);
            Route::post('/leads/scan', [ExhibitorPortalController::class, 'scanLead']);
        });
    });

    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:6,1');
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:10,1');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:6,1');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:6,1');

        Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/email/resend', [EmailVerificationController::class, 'resend'])
                ->middleware('throttle:6,1');
        });
    });

    Route::get('/invitations/{token}', [InvitationController::class, 'accept']);

    Route::middleware(['auth:sanctum', 'resolve.tenant'])->group(function (): void {
        Route::get('/me', [MeController::class, 'show']);

        Route::put('/profile', [ProfileController::class, 'update']);
        Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);

        Route::get('/organizations', [OrganizationController::class, 'index']);
        Route::post('/organizations', [OrganizationController::class, 'store']);

        Route::middleware('tenant.required')->group(function (): void {
            Route::get('/events', [EventController::class, 'index']);
            Route::post('/events', [EventController::class, 'store']);
            Route::get('/events/{event}', [EventController::class, 'show']);
            Route::put('/events/{event}', [EventController::class, 'update']);
            Route::delete('/events/{event}', [EventController::class, 'destroy']);
            Route::post('/events/{event}/publish', [EventController::class, 'publish']);
            Route::post('/events/{event}/archive', [EventController::class, 'archive']);
            Route::post('/events/{event}/duplicate', [EventController::class, 'duplicate']);
            Route::put('/events/{event}/builder', [EventController::class, 'updateBuilder']);
            Route::post('/events/{event}/assets', [EventController::class, 'uploadAsset']);
            Route::delete('/events/{event}/assets/{type}', [EventController::class, 'deleteAsset']);
            Route::get('/events/{event}/preview-token', [EventPreviewTokenController::class, 'show']);
            Route::post('/events/{event}/preview-token', [EventPreviewTokenController::class, 'store']);
            Route::delete('/events/{event}/preview-token', [EventPreviewTokenController::class, 'destroy']);

            Route::get('/events/{event}/ticket-types', [TicketTypeController::class, 'index']);
            Route::post('/events/{event}/ticket-types', [TicketTypeController::class, 'store']);
            Route::put('/events/{event}/ticket-types/{ticketType}', [TicketTypeController::class, 'update']);
            Route::delete('/events/{event}/ticket-types/{ticketType}', [TicketTypeController::class, 'destroy']);

            Route::get('/events/{event}/registration-form', [RegistrationFormController::class, 'show']);
            Route::put('/events/{event}/registration-form', [RegistrationFormController::class, 'update']);

            Route::get('/events/{event}/coupons', [CouponController::class, 'index']);
            Route::post('/events/{event}/coupons', [CouponController::class, 'store']);
            Route::put('/events/{event}/coupons/{coupon}', [CouponController::class, 'update']);
            Route::delete('/events/{event}/coupons/{coupon}', [CouponController::class, 'destroy']);

            Route::get('/events/{event}/orders', [OrderController::class, 'index']);
            Route::get('/events/{event}/orders/{order}', [OrderController::class, 'show']);
            Route::post('/events/{event}/orders/{order}/mark-paid', [OrderController::class, 'markPaid']);
            Route::post('/events/{event}/orders/{order}/refund', [OrderController::class, 'refund']);

            Route::middleware('permission:checkin.scan')->group(function (): void {
                Route::post('/events/{event}/checkin/scan', [CheckInController::class, 'scan']);
                Route::post('/events/{event}/checkin/sync-batch', [CheckInController::class, 'syncBatch']);
                Route::post('/events/{event}/registrations/{registration}/manual-checkin', [CheckInController::class, 'manualCheckIn']);
            });

            Route::get('/events/{event}/checkin/search', [CheckInController::class, 'search']);
            Route::get('/events/{event}/checkin/activity', [CheckInController::class, 'activity']);
            Route::post('/events/{event}/walk-ins', [WalkInController::class, 'store']);
            Route::post('/events/{event}/registrations/{registration}/resend-ticket', [RegistrationController::class, 'resendTicket']);
            Route::post('/events/{event}/registrations/{registration}/cancel', [RegistrationController::class, 'cancel']);
            Route::get('/events/{event}/registrations/{registration}/ticket', [CheckInController::class, 'ticket']);
            Route::get('/events/{event}/registrations/{registration}/badge', [CheckInController::class, 'badge']);
            Route::post('/events/{event}/registrations/{registration}/certificate', [CheckInController::class, 'issueCertificate']);

            Route::get('/events/{event}/tracks', [TrackController::class, 'index']);
            Route::post('/events/{event}/tracks', [TrackController::class, 'store']);
            Route::put('/events/{event}/tracks/{track}', [TrackController::class, 'update']);
            Route::delete('/events/{event}/tracks/{track}', [TrackController::class, 'destroy']);

            Route::get('/events/{event}/sessions', [EventSessionController::class, 'index']);
            Route::post('/events/{event}/sessions', [EventSessionController::class, 'store']);
            Route::put('/events/{event}/sessions/{session}', [EventSessionController::class, 'update']);
            Route::delete('/events/{event}/sessions/{session}', [EventSessionController::class, 'destroy']);

            Route::get('/events/{event}/speakers', [SpeakerController::class, 'index']);
            Route::post('/events/{event}/speakers', [SpeakerController::class, 'store']);
            Route::put('/events/{event}/speakers/{speaker}', [SpeakerController::class, 'update']);
            Route::delete('/events/{event}/speakers/{speaker}', [SpeakerController::class, 'destroy']);

            Route::get('/events/{event}/sponsors', [SponsorController::class, 'index']);
            Route::post('/events/{event}/sponsors', [SponsorController::class, 'store']);
            Route::put('/events/{event}/sponsors/{sponsor}', [SponsorController::class, 'update']);
            Route::delete('/events/{event}/sponsors/{sponsor}', [SponsorController::class, 'destroy']);

            Route::get('/events/{event}/exhibitors', [ExhibitorController::class, 'index']);
            Route::post('/events/{event}/exhibitors', [ExhibitorController::class, 'store']);
            Route::put('/events/{event}/exhibitors/{exhibitor}', [ExhibitorController::class, 'update']);
            Route::delete('/events/{event}/exhibitors/{exhibitor}', [ExhibitorController::class, 'destroy']);
            Route::post('/events/{event}/exhibitors/{exhibitor}/invite-contact', [ExhibitorController::class, 'inviteContact']);

            Route::get('/events/{event}/guest-invites', [GuestInviteController::class, 'index']);
            Route::post('/events/{event}/guest-invites', [GuestInviteController::class, 'store']);
            Route::post('/events/{event}/guest-invites/import', [GuestInviteController::class, 'import']);
            Route::post('/events/{event}/guest-invites/send-bulk', [GuestInviteController::class, 'sendBulk']);
            Route::put('/events/{event}/guest-invites/{guestInvite}', [GuestInviteController::class, 'update']);
            Route::delete('/events/{event}/guest-invites/{guestInvite}', [GuestInviteController::class, 'destroy']);
            Route::post('/events/{event}/guest-invites/{guestInvite}/send', [GuestInviteController::class, 'send']);
            Route::post('/events/{event}/guest-invites/{guestInvite}/remind', [GuestInviteController::class, 'remind']);
            Route::post('/events/{event}/guest-invites/remind-all', [GuestInviteController::class, 'remindAll']);

            Route::get('/events/{event}/tables', [EventTableController::class, 'index']);
            Route::post('/events/{event}/tables', [EventTableController::class, 'store']);
            Route::put('/events/{event}/tables/{eventTable}', [EventTableController::class, 'update']);
            Route::delete('/events/{event}/tables/{eventTable}', [EventTableController::class, 'destroy']);

            Route::get('/events/{event}/seating', [EventSeatingController::class, 'show']);
            Route::put('/events/{event}/seating/assignments', [EventSeatingController::class, 'assign']);

            Route::get('/events/{event}/conference/analytics', [ConferenceAnalyticsController::class, 'show']);

            Route::get('/events/{event}/analytics', [EventDashboardController::class, 'metrics']);
            Route::get('/events/{event}/dietary-summary', [EventDashboardController::class, 'dietarySummary']);
            Route::get('/events/{event}/activity', [OrganizationDashboardController::class, 'eventActivity']);
            Route::get('/organization/analytics', [OrganizationDashboardController::class, 'metrics']);
            Route::get('/organization/activity', [OrganizationDashboardController::class, 'activity']);
            Route::get('/exports/{exportId}', [EventDashboardController::class, 'exportStatus']);
            Route::get('/exports/{exportId}/download', [EventDashboardController::class, 'downloadExport']);

            Route::middleware('permission:reports.export')->group(function (): void {
                Route::post('/events/{event}/exports/{type}', [EventDashboardController::class, 'export']);
                Route::get('/events/{event}/exports/{type}', [EventDashboardController::class, 'export']);
            });

            Route::get('/tenant-fixtures', [TenantFixtureController::class, 'index']);
            Route::post('/tenant-fixtures', [TenantFixtureController::class, 'store']);
        });

        Route::middleware('org.member')->group(function (): void {
            Route::get('/organizations/{organization}', [OrganizationController::class, 'show']);
            Route::put('/organizations/{organization}', [OrganizationController::class, 'update']);
            Route::get('/organizations/{organization}/members', [OrganizationController::class, 'members']);
            Route::put('/organizations/{organization}/members/{user}', [OrganizationController::class, 'updateMemberRole']);
            Route::get('/organizations/{organization}/roles', [OrganizationController::class, 'roles']);
            Route::post('/organizations/{organization}/roles', [OrganizationController::class, 'storeRole']);

            Route::get('/organizations/{organization}/invitations', [InvitationController::class, 'index']);
            Route::post('/organizations/{organization}/invitations', [InvitationController::class, 'store']);
            Route::delete('/organizations/{organization}/invitations/{invitation}', [InvitationController::class, 'destroy']);
            Route::post('/organizations/{organization}/invitations/{invitation}/resend', [InvitationController::class, 'resend']);
        });

        Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept']);
    });
});
