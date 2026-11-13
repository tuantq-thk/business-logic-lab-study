<?php

use App\Features\StateTransition\Actions\ChangeSupportTicketStatus;
use App\Features\StateTransition\Enums\SupportTicketStatus;
use App\Features\StateTransition\Models\SupportTicket;
use App\Features\StateTransition\Models\TicketStatusHistory;
use App\Models\CatalogSetting;
use App\Models\User;
use Database\Seeders\AdminCatalogSeeder;
use Database\Seeders\StateTransition\StateTransitionSeeder;
use Illuminate\Database\QueryException;

function stateTransitionAdmin(): User
{
    $admin = User::factory()->create();
    $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

    return $admin;
}

beforeEach(function () {
    $this->seed(AdminCatalogSeeder::class);
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);
});

test('an admin can transition an open ticket and the action records history', function () {
    $admin = stateTransitionAdmin();
    $ticket = SupportTicket::factory()->create();

    $this->actingAs($admin)
        ->patch(route('labs.state-transition.transition', $ticket), [
            'to_status' => SupportTicketStatus::InProgress->value,
            'reason' => 'Đã tiếp nhận để xử lý.',
            'version' => 0,
        ])
        ->assertRedirect(route('labs.state-transition.index'));

    expect($ticket->refresh())
        ->status->toBe(SupportTicketStatus::InProgress)
        ->assigned_to->toBe($admin->id)
        ->version->toBe(1);

    $this->assertDatabaseHas('ticket_status_histories', [
        'ticket_id' => $ticket->id,
        'from_status' => SupportTicketStatus::Open->value,
        'to_status' => SupportTicketStatus::InProgress->value,
        'actor_id' => $admin->id,
        'reason' => 'Đã tiếp nhận để xử lý.',
    ]);
});

test('an invalid transition is rejected without changing the ticket', function () {
    $admin = stateTransitionAdmin();
    $ticket = SupportTicket::factory()->create();

    $this->actingAs($admin)
        ->from(route('labs.state-transition.index'))
        ->patch(route('labs.state-transition.transition', $ticket), [
            'to_status' => SupportTicketStatus::Closed->value,
            'version' => 0,
        ])
        ->assertRedirect(route('labs.state-transition.index'))
        ->assertSessionHasErrors('transition')
        ->assertSessionHas('error_ticket_id', $ticket->id);

    expect($ticket->refresh()->status)->toBe(SupportTicketStatus::Open);
    expect(TicketStatusHistory::query()->where('ticket_id', $ticket->id)->count())->toBe(1);
});

test('a transition error is rendered in the ticket flow block', function () {
    $admin = stateTransitionAdmin();
    $ticket = SupportTicket::factory()->create();

    $this->actingAs($admin)
        ->from(route('labs.state-transition.index'))
        ->patch(route('labs.state-transition.transition', $ticket), [
            'to_status' => SupportTicketStatus::Closed->value,
            'version' => 0,
        ])
        ->assertRedirect(route('labs.state-transition.index'));

    $this->get(route('labs.state-transition.index'))
        ->assertOk()
        ->assertSee('Không thể chuyển ticket từ [Mới mở] sang [Đã đóng].');
});

test('a learner cannot transition a support ticket', function () {
    $ticket = SupportTicket::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('labs.state-transition.transition', $ticket), [
            'to_status' => SupportTicketStatus::InProgress->value,
            'version' => 0,
        ])
        ->assertForbidden();
});

test('reopening a closed ticket requires a reason', function () {
    $admin = stateTransitionAdmin();
    $ticket = SupportTicket::factory()->closed()->create();

    $this->actingAs($admin)
        ->from(route('labs.state-transition.index'))
        ->patch(route('labs.state-transition.transition', $ticket), [
            'to_status' => SupportTicketStatus::Open->value,
            'version' => 3,
        ])
        ->assertRedirect(route('labs.state-transition.index'))
        ->assertSessionHasErrors('reason');

    expect($ticket->refresh()->status)->toBe(SupportTicketStatus::Closed);
});

test('a stale transition returns conflict and does not create history', function () {
    $admin = stateTransitionAdmin();
    $ticket = SupportTicket::factory()->inProgress()->create();

    $this->actingAs($admin)
        ->withHeaders(['Accept' => 'application/json'])
        ->from(route('labs.state-transition.index'))
        ->patch(route('labs.state-transition.transition', $ticket), [
            'to_status' => SupportTicketStatus::InProgress->value,
            'version' => 0,
        ])
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'ticket_stale');

    expect(TicketStatusHistory::query()->where('ticket_id', $ticket->id)->count())->toBe(2);
});

test('the transition matrix accepts only the defined state changes', function () {
    $admin = stateTransitionAdmin();
    $factoryStates = [
        SupportTicketStatus::Open->value => null,
        SupportTicketStatus::InProgress->value => 'inProgress',
        SupportTicketStatus::Resolved->value => 'resolved',
        SupportTicketStatus::Closed->value => 'closed',
    ];
    $allowedTransitions = [
        SupportTicketStatus::Open->value => [SupportTicketStatus::InProgress->value],
        SupportTicketStatus::InProgress->value => [SupportTicketStatus::Resolved->value],
        SupportTicketStatus::Resolved->value => [SupportTicketStatus::Closed->value],
        SupportTicketStatus::Closed->value => [SupportTicketStatus::Open->value],
    ];

    foreach ($factoryStates as $fromStatus => $factoryState) {
        foreach (SupportTicketStatus::cases() as $toStatus) {
            $ticket = $factoryState === null
                ? SupportTicket::factory()->create()
                : SupportTicket::factory()->{$factoryState}()->create();
            $allowed = in_array($toStatus->value, $allowedTransitions[$fromStatus], true);
            $request = $this->actingAs($admin)
                ->from(route('labs.state-transition.index'))
                ->patch(route('labs.state-transition.transition', $ticket), [
                    'to_status' => $toStatus->value,
                    'reason' => $fromStatus === SupportTicketStatus::Closed->value && $toStatus === SupportTicketStatus::Open ? 'Reopen for follow-up.' : null,
                    'version' => $ticket->version,
                ]);

            if ($allowed) {
                $request->assertRedirect(route('labs.state-transition.index'));
                expect($ticket->refresh()->status)->toBe($toStatus);
            } else {
                $request->assertRedirect(route('labs.state-transition.index'))->assertSessionHasErrors('transition');
                expect($ticket->refresh()->status->value)->toBe($fromStatus);
            }
        }
    }
});

test('an owner can change the status of their own ticket', function () {
    $owner = User::factory()->create();
    $ticket = SupportTicket::factory()->create(['created_by' => $owner->id]);

    $this->actingAs($owner)
        ->patch(route('labs.state-transition.transition', $ticket), [
            'to_status' => SupportTicketStatus::InProgress->value,
            'version' => 0,
        ])
        ->assertRedirect();

    expect($ticket->refresh()->status)->toBe(SupportTicketStatus::InProgress);
});

test('an owner sees the status change control for their ticket', function () {
    $owner = User::factory()->create();
    SupportTicket::factory()->create(['created_by' => $owner->id]);

    $this->actingAs($owner)
        ->get(route('labs.state-transition.index'))
        ->assertOk()
        ->assertSee('Theo dõi tiến độ phiếu hỗ trợ')
        ->assertSee('Request')
        ->assertSee('Decision')
        ->assertSee('Commit')
        ->assertSee('Chuyển trạng thái')
        ->assertSee('Lưu thay đổi');
});

test('an owner cannot change another owner’s ticket', function () {
    $owner = User::factory()->create();
    $otherOwner = User::factory()->create();
    $ticket = SupportTicket::factory()->create(['created_by' => $otherOwner->id]);

    $this->actingAs($owner)
        ->patch(route('labs.state-transition.transition', $ticket), [
            'to_status' => SupportTicketStatus::InProgress->value,
            'version' => 0,
        ])
        ->assertForbidden();
});

test('an unauthorized actor cannot use a stale version to probe ticket state', function () {
    $owner = User::factory()->create();
    $otherOwner = User::factory()->create();
    $ticket = SupportTicket::factory()->create(['created_by' => $otherOwner->id]);

    $this->actingAs($owner)
        ->patch(route('labs.state-transition.transition', $ticket), [
            'to_status' => SupportTicketStatus::InProgress->value,
            'version' => 999,
        ])
        ->assertForbidden();
});

test('an admin can close any resolved ticket', function () {
    $admin = stateTransitionAdmin();
    $ticket = SupportTicket::factory()->resolved()->create();

    $this->actingAs($admin)
        ->patch(route('labs.state-transition.transition', $ticket), [
            'to_status' => SupportTicketStatus::Closed->value,
            'version' => 2,
        ])
        ->assertRedirect();

    expect($ticket->refresh()->status)->toBe(SupportTicketStatus::Closed);
});

test('creating a ticket records its initial state through the create action', function () {
    $admin = stateTransitionAdmin();

    $this->actingAs($admin)
        ->post(route('labs.state-transition.store'), ['subject' => 'Cannot access report'])
        ->assertRedirect(route('labs.state-transition.index'));

    $ticket = SupportTicket::query()->where('subject', 'Cannot access report')->firstOrFail();

    expect($ticket->status)->toBe(SupportTicketStatus::Open)
        ->and($ticket->version)->toBe(0);
    $this->assertDatabaseHas('ticket_status_histories', [
        'ticket_id' => $ticket->id,
        'from_status' => null,
        'to_status' => SupportTicketStatus::Open->value,
        'actor_id' => $admin->id,
    ]);
});

test('status and version cannot be changed through mass assignment', function () {
    $ticket = SupportTicket::factory()->create();

    $ticket->fill([
        'status' => SupportTicketStatus::Closed,
        'version' => 99,
    ])->save();

    expect($ticket->refresh()->status)->toBe(SupportTicketStatus::Open);
    expect($ticket->version)->toBe(0);
});

test('status history rejects update and delete operations', function () {
    $history = SupportTicket::factory()->create()->histories()->firstOrFail();

    expect(fn () => $history->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class, 'append-only');
    expect(fn () => $history->delete())
        ->toThrow(LogicException::class, 'cannot be deleted');
});

test('a ticket with history cannot be deleted', function () {
    $ticket = SupportTicket::factory()->create();

    expect(fn () => $ticket->delete())
        ->toThrow(QueryException::class);

    expect(TicketStatusHistory::query()->where('ticket_id', $ticket->id)->count())->toBe(1);
});

test('the demo seeder creates a consistent ticket for every status', function () {
    User::factory()->create();
    $this->seed(StateTransitionSeeder::class);

    $statuses = SupportTicket::query()->get(['subject', 'status'])->mapWithKeys(
        fn (SupportTicket $ticket): array => [$ticket->subject => $ticket->status->value],
    )->all();

    expect($statuses)->toMatchArray([
        'Demo: ticket mới' => SupportTicketStatus::Open->value,
        'Demo: ticket đang xử lý' => SupportTicketStatus::InProgress->value,
        'Demo: ticket đã xử lý' => SupportTicketStatus::Resolved->value,
        'Demo: ticket đã đóng' => SupportTicketStatus::Closed->value,
    ]);

    expect(SupportTicket::query()->where('subject', 'Demo: ticket đã đóng')->value('version'))->toBe(3);
});

test('ticket and history roll back together when history persistence fails', function () {
    $admin = stateTransitionAdmin();
    $ticket = SupportTicket::factory()->create();

    TicketStatusHistory::creating(function (): void {
        throw new RuntimeException('history write failed');
    });

    try {
        expect(fn () => app(ChangeSupportTicketStatus::class)->handle(
            ticket: $ticket,
            actor: $admin,
            toStatus: SupportTicketStatus::InProgress,
            reason: 'Đã tiếp nhận để xử lý.',
            expectedVersion: 0,
        ))->toThrow(RuntimeException::class, 'history write failed');
    } finally {
        TicketStatusHistory::flushEventListeners();
    }

    expect($ticket->refresh()->status)->toBe(SupportTicketStatus::Open);
    expect($ticket->version)->toBe(0);
    expect(TicketStatusHistory::query()->where('ticket_id', $ticket->id)->count())->toBe(1);
});
