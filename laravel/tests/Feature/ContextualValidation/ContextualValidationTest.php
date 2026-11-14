<?php

use App\Features\ContextualValidation\Actions\RegisterForEvent;
use App\Features\ContextualValidation\Enums\RegistrationStatus;
use App\Features\ContextualValidation\Enums\TicketType;
use App\Features\ContextualValidation\Exceptions\RegistrationAlreadyExists;
use App\Features\ContextualValidation\Exceptions\RegistrationCapacityReached;
use App\Features\ContextualValidation\Exceptions\RegistrationEligibilityFailed;
use App\Features\ContextualValidation\Exceptions\RegistrationWindowClosed;
use App\Features\ContextualValidation\Models\EventRegistration;
use App\Features\ContextualValidation\Models\WorkshopEvent;
use App\Models\CatalogSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\AdminCatalogSeeder;

function contextualNow(): CarbonImmutable
{
    return CarbonImmutable::parse('2030-01-10 12:00:00', 'UTC');
}

test('contextual registration separates valid shape from eligibility policy', function () {
    $event = WorkshopEvent::factory()->create();
    $attendee = User::factory()->create();

    $registration = app(RegisterForEvent::class)->handle(
        $event,
        $attendee,
        TicketType::Standard,
        null,
        contextualNow(),
    );

    expect($registration->status)->toBe(RegistrationStatus::Confirmed)
        ->and($registration->attendee_id)->toBe($attendee->id);
});

test('authoritative event and registration fields are protected from mass assignment', function () {
    $event = WorkshopEvent::factory()->create();
    $registration = EventRegistration::factory()->create(['event_id' => $event->id]);

    $event->fill(['status' => 'closed', 'invite_code_hash' => 'plaintext'])->save();
    $registration->fill(['status' => 'cancelled'])->save();

    expect($event->refresh()->status->value)->toBe('open')
        ->and($event->invite_code_hash)->toBeNull()
        ->and($registration->refresh()->status->value)->toBe('confirmed');
});

test('registration rejects before and after the registration window', function () {
    $event = WorkshopEvent::factory()->create([
        'registration_opens_at_utc' => '2030-01-11 00:00:00',
    ]);

    expect(fn () => app(RegisterForEvent::class)->handle($event, User::factory()->create(), TicketType::Standard, null, contextualNow()))
        ->toThrow(RegistrationWindowClosed::class);

    $event->update([
        'registration_opens_at_utc' => '2030-01-01 00:00:00',
        'registration_closes_at_utc' => '2030-01-10 12:00:00',
    ]);

    expect(fn () => app(RegisterForEvent::class)->handle($event, User::factory()->create(), TicketType::Standard, null, contextualNow()))
        ->toThrow(RegistrationWindowClosed::class);
});

test('registration accepts the exact opening boundary and rejects the exact closing boundary', function () {
    $event = WorkshopEvent::factory()->create([
        'registration_opens_at_utc' => '2030-01-10 12:00:00',
        'registration_closes_at_utc' => '2030-01-10 13:00:00',
    ]);
    $action = app(RegisterForEvent::class);

    expect(fn () => $action->handle($event, User::factory()->create(), TicketType::Standard, null, contextualNow()))
        ->not->toThrow(RegistrationWindowClosed::class);

    expect(fn () => $action->handle(
        $event,
        User::factory()->create(),
        TicketType::Standard,
        null,
        contextualNow()->setTime(13, 0),
    ))->toThrow(RegistrationWindowClosed::class);
});

test('registration enforces invite eligibility without exposing the stored code', function () {
    $event = WorkshopEvent::factory()->inviteOnly('SECRET-CODE')->create();
    $action = app(RegisterForEvent::class);

    expect(fn () => $action->handle($event, User::factory()->create(), TicketType::Standard, 'SECRET-CODE', contextualNow()))
        ->toThrow(RegistrationEligibilityFailed::class);

    expect(fn () => $action->handle($event, User::factory()->create(), TicketType::Invite, 'wrong', contextualNow()))
        ->toThrow(RegistrationEligibilityFailed::class);

    $registration = $action->handle($event, User::factory()->create(), TicketType::Invite, 'SECRET-CODE', contextualNow());

    expect($registration->exists)->toBeTrue()
        ->and($event->refresh()->invite_code_hash)->not->toBe('SECRET-CODE');
});

test('registration rejects duplicate attendee and event pair', function () {
    $event = WorkshopEvent::factory()->create();
    $attendee = User::factory()->create();
    $action = app(RegisterForEvent::class);
    $action->handle($event, $attendee, TicketType::Standard, null, contextualNow());

    expect(fn () => $action->handle($event, $attendee, TicketType::Standard, null, contextualNow()))
        ->toThrow(RegistrationAlreadyExists::class);
});

test('registration protects capacity inside the write use case', function () {
    $event = WorkshopEvent::factory()->create(['capacity' => 1]);
    $action = app(RegisterForEvent::class);
    $action->handle($event, User::factory()->create(), TicketType::Standard, null, contextualNow());

    expect(fn () => $action->handle($event, User::factory()->create(), TicketType::Standard, null, contextualNow()))
        ->toThrow(RegistrationCapacityReached::class);
});

test('contextual validation route renders policy explanation and event state', function () {
    $this->seed(AdminCatalogSeeder::class);
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);
    $event = WorkshopEvent::factory()->create(['title' => 'Context event demo']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('labs.contextual-validation.index'))
        ->assertOk()
        ->assertSee(__('contextual_validation.learning.theory_title'))
        ->assertSee(__('contextual_validation.learning.examples_title'))
        ->assertSee(__('contextual_validation.events.how_to'))
        ->assertSee('StoreEventRegistrationRequest')
        ->assertSee($event->title);
});

test('contextual validation registration maps business conflict to localized feedback', function () {
    $this->seed(AdminCatalogSeeder::class);
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);
    $event = WorkshopEvent::factory()->create();
    $user = User::factory()->create();
    EventRegistration::factory()->create(['event_id' => $event->id, 'attendee_id' => $user->id]);

    $this->from(route('labs.contextual-validation.index'))
        ->actingAs($user)
        ->post(route('labs.contextual-validation.store'), [
            'event_id' => $event->id,
            'ticket_type' => TicketType::Standard->value,
        ])
        ->assertRedirect(route('labs.contextual-validation.index').'#practice')
        ->assertSessionHasErrors(['registration' => __('contextual_validation.errors.duplicate')]);
});

test('successful registration redirects back to the practice section', function () {
    $this->travelTo(contextualNow());
    $this->seed(AdminCatalogSeeder::class);
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);
    $event = WorkshopEvent::factory()->create();
    $user = User::factory()->create();

    $this->from(route('labs.contextual-validation.index'))
        ->actingAs($user)
        ->post(route('labs.contextual-validation.store'), [
            'event_id' => $event->id,
            'ticket_type' => TicketType::Standard->value,
        ])
        ->assertRedirect(route('labs.contextual-validation.index').'#practice')
        ->assertSessionHas('status', __('contextual_validation.flash.registered'));
});
