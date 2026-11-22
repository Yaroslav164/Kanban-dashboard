<?php

declare(strict_types=1);

use App\Events\BoardChanged;
use App\Models\User;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = User::query()->forceCreate(['name' => 'Owner', 'email' => 'realtime@example.test']);
});

it('signs only the authenticated account channel', function () {
    config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb' => [
        'driver' => 'reverb',
        'app_id' => 'test-app',
        'key' => 'test-key',
        'secret' => 'test-secret',
        'options' => ['host' => '127.0.0.1', 'port' => 8080, 'scheme' => 'http', 'useTLS' => false],
    ]]);
    $data = ['socket_id' => '123.456', 'channel_name' => "private-users.{$this->owner->id}"];
    $this->postJson('/api/broadcasting/auth', $data)->assertUnauthorized();

    $signature = hash_hmac('sha256', "123.456:{$data['channel_name']}", 'test-secret');
    $this->actingAs($this->owner)->postJson('/api/broadcasting/auth', $data)
        ->assertOk()->assertExactJson(['auth' => "test-key:$signature"]);
    $this->postJson('/api/broadcasting/auth', [...$data, 'channel_name' => 'private-users.99999'])
        ->assertForbidden();
    $this->postJson('/api/broadcasting/auth', ['socket_id' => 'bad', 'channel_name' => 'public-users.1'])
        ->assertUnprocessable()->assertJsonValidationErrors(['socket_id', 'channel_name']);
});

it('dispatches board changes only after commit', function () {
    $this->actingAs($this->owner);
    $events = [];
    Event::listen(BoardChanged::class, function (BoardChanged $event) use (&$events): void {
        $events[] = $event;
    });

    expect(fn () => DB::transaction(function () use (&$events) {
        $this->postJson('/api/boards', ['title' => 'Rolled back'])->assertCreated();
        expect($events)->toBe([]);

        throw new RuntimeException('Roll back the outer transaction');
    }))->toThrow(RuntimeException::class, 'Roll back the outer transaction');

    $this->assertDatabaseMissing('boards', ['title' => 'Rolled back']);
    $board = $this->postJson('/api/boards', ['title' => 'Committed'])->assertCreated()->json('data.id');
    expect($events)->toHaveCount(1);
    expect(array_column($events[0]->broadcastOn(), 'name'))->toBe(["private-users.{$this->owner->id}"]);
    expect($events[0]->broadcastAs())->toBe('board.changed');
    expect($events[0]->broadcastWith())->toBe(['board_id' => $board]);
});

it('keeps successful board writes successful if broadcasting fails', function () {
    $broadcaster = Mockery::mock(Broadcaster::class);
    $broadcaster->shouldReceive('broadcast')->once()->andThrow(new RuntimeException('Broadcast unavailable'));
    Broadcast::extend('broken', fn () => $broadcaster);
    config(['broadcasting.default' => 'broken', 'broadcasting.connections.broken' => ['driver' => 'broken']]);
    config(['queue.default' => 'database']);

    $board = $this->actingAs($this->owner)->postJson('/api/boards', ['title' => 'Saved'])->assertCreated()->json('data.id');
    $this->assertDatabaseHas('boards', ['id' => $board, 'title' => 'Saved']);
});
