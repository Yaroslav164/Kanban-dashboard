<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = User::query()->create(['name' => 'Owner', 'email' => 'history-owner@example.test']);
    $this->member = User::query()->create(['name' => 'Member', 'email' => 'teammate@example.test']);
    $this->outsider = User::query()->create(['name' => 'Outsider', 'email' => 'outsider@example.test']);
    $this->actingAs($this->owner);
    $this->boardId = $this->postJson('/api/boards', ['title' => 'Launch'])->assertCreated()->json('data.id');
    $boardUrl = "/api/boards/{$this->boardId}";
    $this->postJson("$boardUrl/members", ['email' => $this->member->email, 'role' => 'viewer'])->assertCreated();
    $this->from = $this->postJson("$boardUrl/columns", ['title' => 'Inbox'])->assertCreated()->json('data.id');
    $this->to = $this->postJson("$boardUrl/columns", ['title' => 'Review'])->assertCreated()->json('data.id');
    $this->cardId = $this->postJson("/api/columns/{$this->from}/cards", ['title' => 'Release'])->assertCreated()->json('data.id');
    $this->cardUrl = "/api/columns/{$this->from}/cards/{$this->cardId}";
    $this->activityUrl = "/api/cards/{$this->cardId}";
    $this->inbox = '/api/me/notifications';
});

it('records only committed changes and scopes card history to the board', function () {
    $this->patchJson($this->cardUrl, ['due_date' => '2026-11-30', 'assignee_ids' => [$this->member->id]])->assertOk();
    $this->patchJson($this->cardUrl, ['due_date' => '2026-11-30'])->assertOk();
    $this->patchJson($this->cardUrl, ['completed' => true])->assertOk();
    $this->postJson("$this->activityUrl/move", [
        'column_id' => $this->to, 'previous_card_id' => null, 'next_card_id' => null,
    ])->assertOk();
    $this->actingAs($this->member)->getJson("$this->activityUrl/history")->assertOk()
        ->assertJsonCount(4, 'data')
        ->assertJsonPath('data.0.kind', 'moved')
        ->assertJsonPath('data.0.detail', 'Review')
        ->assertJsonPath('data.1.kind', 'completed')
        ->assertJsonPath('data.3.kind', 'due_date')
        ->assertJsonPath('data.3.actor_name', $this->owner->name);
    $this->actingAs($this->outsider)->getJson("$this->activityUrl/history")->assertNotFound();
});

it('delivers private assignment, mention and due notifications once and supports reading them', function () {
    $this->travelTo(now()->setDate(2026, 11, 24)->startOfDay());
    $this->patchJson($this->cardUrl, ['due_date' => '2026-11-25', 'assignee_ids' => [$this->member->id]])->assertOk();
    $this->patchJson($this->cardUrl, ['assignee_ids' => [$this->member->id]])->assertOk();
    $this->postJson("$this->activityUrl/comments", ['body' => 'Please check @teammate@example.test, @outsider@example.test, @teammate@example.test.'])->assertCreated();
    $notification = $this->actingAs($this->member)->getJson($this->inbox)->assertOk()
        ->assertJsonCount(3, 'data')->assertJsonPath('data.0.kind', 'due_soon')
        ->assertJsonPath('data.1.kind', 'mentioned')->assertJsonPath('data.2.kind', 'assigned')
        ->assertJsonPath('data.1.card_id', $this->cardId)->json('data.1.id');
    $this->actingAs($this->outsider)->getJson($this->inbox)->assertOk()->assertJsonCount(0, 'data');
    $this->postJson("$this->inbox/$notification/read")->assertNotFound();
    $this->actingAs($this->member)->postJson("$this->inbox/$notification/read")->assertNoContent();
    $this->getJson($this->inbox)->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('data.2.read', true);
    $this->actingAs($this->owner)->deleteJson("/api/boards/{$this->boardId}/members/{$this->member->id}")->assertNoContent();
    $this->actingAs($this->member)->getJson($this->inbox)->assertOk()->assertJsonCount(0, 'data');
});
