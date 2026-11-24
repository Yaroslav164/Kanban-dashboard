<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = User::query()->create(['name' => 'Owner', 'email' => 'card-owner@example.test']);
    $this->member = User::query()->create(['name' => 'Member', 'email' => 'card-member@example.test']);
    $this->outsider = User::query()->create(['name' => 'Outsider', 'email' => 'card-outsider@example.test']);
    $this->actingAs($this->owner);
    $this->board = $this->postJson('/api/boards', ['title' => 'Cards'])->assertCreated()->json('data.id');
    $this->boardUrl = "/api/boards/{$this->board}";
    $this->postJson("$this->boardUrl/members", ['email' => $this->member->email, 'role' => 'viewer'])->assertCreated();
    $column = $this->postJson("$this->boardUrl/columns", ['title' => 'Inbox'])->assertCreated()->json('data.id');
    $this->cardsUrl = "/api/columns/$column/cards";
    $card = $this->postJson($this->cardsUrl, ['title' => 'Card'])->assertCreated()->json('data.id');
    $this->cardUrl = "$this->cardsUrl/$card";
    $this->activityUrl = "/api/cards/$card";
});

it('creates cards with optional details and rolls back invalid assignees', function () {
    $this->postJson($this->cardsUrl, [
        'title' => 'Release',
        'due_date' => '2026-11-30',
        'assignee_ids' => [$this->member->id],
        'labels' => [['name' => 'Release', 'color' => '#af0000']],
    ])->assertCreated()
        ->assertJsonPath('data.due_date', '2026-11-30')
        ->assertJsonPath('data.assignee_ids', [$this->member->id])
        ->assertJsonPath('data.labels.0.name', 'Release');

    $this->postJson($this->cardsUrl, ['title' => 'Invalid', 'assignee_ids' => [$this->outsider->id]])
        ->assertUnprocessable()->assertJsonValidationErrors('assignee_ids');
    $this->assertDatabaseMissing('cards', ['title' => 'Invalid']);
});

it('persists card details in the board snapshot and removes revoked assignees', function () {
    $details = [
        'start_date' => '2026-11-20',
        'due_date' => '2026-11-25',
        'completed' => true,
        'labels' => [['name' => 'Priority', 'color' => '#af0000']],
        'assignee_ids' => [$this->member->id],
        'checklists' => [[
            'id' => '55d395ec-eda0-4679-9bef-042c2315341f',
            'title' => 'Review',
            'items' => [[
                'id' => '07d5635e-c724-44c8-80b0-9d489b7602d7',
                'title' => 'Check dates',
                'completed' => true,
            ]],
        ]],
    ];
    $this->patchJson($this->cardUrl, $details)->assertOk();
    $snapshot = $this->getJson($this->boardUrl)->assertOk();
    foreach (['start_date', 'due_date', 'completed', 'assignee_ids'] as $field) {
        $snapshot->assertJsonPath("data.columns.0.cards.0.$field", $details[$field]);
    }
    $snapshot->assertJsonPath('data.columns.0.cards.0.labels.0.name', 'Priority');
    $snapshot->assertJsonPath('data.columns.0.cards.0.checklists.0.items.0.completed', true);
    $this->deleteJson("$this->boardUrl/members/{$this->member->id}")->assertNoContent();
    $this->getJson($this->cardUrl)->assertOk()->assertJsonPath('data.assignee_ids', []);
});

it('validates partial date edits, board membership and nested card details', function () {
    $this->patchJson($this->cardUrl, ['due_date' => '2026-11-25'])->assertOk();
    $this->patchJson($this->cardUrl, ['start_date' => '2026-11-26'])
        ->assertUnprocessable()->assertJsonValidationErrors('due_date');
    $this->patchJson($this->cardUrl, ['assignee_ids' => [$this->outsider->id]])
        ->assertUnprocessable()->assertJsonValidationErrors('assignee_ids');
    $this->patchJson($this->cardUrl, ['labels' => [['name' => 'Label', 'color' => 'red', 'extra' => 'value']]])
        ->assertUnprocessable()->assertJsonValidationErrors('labels.0');
    $this->actingAs($this->member)->patchJson($this->cardUrl, ['completed' => true])->assertForbidden();
});

it('rejects a stale card update while allowing independent fields to be saved', function () {
    $this->patchJson($this->cardUrl, ['title' => 'First edit', 'base' => ['title' => 'Card']])->assertOk();
    $this->patchJson($this->cardUrl, [
        'title' => 'Stale edit',
        'base' => ['title' => 'Card'],
    ])->assertConflict()->assertJsonPath('message', 'Some card fields have changed. Refresh the card before saving again.');
    $this->patchJson($this->cardUrl, ['due_date' => '2026-11-30', 'base' => ['due_date' => null]])
        ->assertOk()->assertJsonPath('data.title', 'First edit');
    $this->patchJson($this->cardUrl, ['title' => 'Invalid', 'base' => ['due_date' => null]])
        ->assertUnprocessable()->assertJsonValidationErrors('base');
    $this->patchJson($this->cardUrl, ['title' => 'Invalid', 'base' => ['title' => 'First edit', 'column_id' => 1]])
        ->assertUnprocessable()->assertJsonValidationErrors('base');
});

it('accepts original values regardless of object key and assignee order', function () {
    $checklist = [
        'items' => [['completed' => false, 'title' => 'Publish', 'id' => '07d5635e-c724-44c8-80b0-9d489b7602d7']],
        'title' => 'Release',
        'id' => '55d395ec-eda0-4679-9bef-042c2315341f',
    ];
    $this->patchJson($this->cardUrl, [
        'assignee_ids' => [$this->owner->id, $this->member->id],
        'checklists' => [$checklist],
    ])->assertOk();

    $this->patchJson($this->cardUrl, [
        'assignee_ids' => [],
        'checklists' => [],
        'base' => ['checklists' => [$checklist], 'assignee_ids' => [$this->member->id, $this->owner->id]],
    ])->assertOk()->assertJsonPath('data.checklists', [])->assertJsonPath('data.assignee_ids', []);
});

it('shares comments on a card with its board members', function () {
    $comment = $this->postJson("$this->activityUrl/comments", ['body' => 'Ready for review'])
        ->assertCreated()->assertJsonPath('data.author.name', 'Owner')->json('data.id');
    $this->actingAs($this->member);
    $this->getJson("$this->activityUrl/activity")->assertOk()->assertJsonPath('data.comments.0.body', 'Ready for review');
    $this->postJson("$this->activityUrl/comments", ['body' => 'Read only'])->assertForbidden();
    $this->actingAs($this->outsider)->getJson("$this->activityUrl/activity")->assertNotFound();
    $this->actingAs($this->owner)->deleteJson("$this->activityUrl/comments/$comment")->assertNoContent();
    $this->getJson("$this->activityUrl/activity")->assertOk()->assertJsonCount(0, 'data.comments');
});

it('keeps file content private and streams the original bytes to members', function () {
    $bytes = "Private file\x00\xFF\n";
    $file = UploadedFile::fake()->createWithContent('project.bin', $bytes);
    $attachment = $this->post("$this->activityUrl/attachments", ['file' => $file], ['Accept' => 'application/json'])
        ->assertCreated()->assertJsonPath('data.name', 'project.bin')->json('data.id');
    $url = "$this->activityUrl/attachments/$attachment";
    $this->actingAs($this->outsider)->get($url)->assertNotFound();
    $this->actingAs($this->member)->get($url)->assertOk()->assertContent($bytes)
        ->assertDownload('project.bin')->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->get("$url?inline=1")->assertOk()->assertDownload('project.bin')
        ->assertHeader('Content-Type', 'application/octet-stream');
    $this->deleteJson($url)->assertForbidden();
    $this->actingAs($this->owner)->deleteJson($url)->assertNoContent();
    $this->get($url)->assertNotFound();
});

it('limits the number of attachments per card and frees a slot after deletion', function () {
    $url = "$this->activityUrl/attachments";
    $upload = fn () => $this->post($url, [
        'file' => UploadedFile::fake()->createWithContent('project.txt', 'A project file.'),
    ], ['Accept' => 'application/json']);

    $first = $upload()->assertCreated()->json('data.id');
    for ($count = 1; $count < 20; $count++) {
        $upload()->assertCreated();
    }
    $upload()->assertUnprocessable()->assertJsonValidationErrors('file');

    $this->deleteJson("$url/$first")->assertNoContent();
    $upload()->assertCreated();
});
