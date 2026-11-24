<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = User::query()->create(['name' => 'Owner', 'email' => 'template-owner@example.test']);
    $this->member = User::query()->create(['name' => 'Member', 'email' => 'template-member@example.test']);
    $this->actingAs($this->owner);
    $this->board = '/api/boards/'.$this->postJson('/api/boards', ['title' => 'Board'])->assertCreated()->json('data.id');
    $this->templates = "$this->board/card-templates";
    $column = $this->postJson("$this->board/columns", ['title' => 'Inbox'])->assertCreated()->json('data.id');
    $this->cards = "/api/columns/$column/cards";
});

it('keeps a reusable snapshot with fresh incomplete checklists', function () {
    $checklistId = '55d395ec-eda0-4679-9bef-042c2315341f';
    $itemId = '07d5635e-c724-44c8-80b0-9d489b7602d7';
    $labels = [['name' => 'Release', 'color' => '#abcdef']];
    $source = $this->postJson($this->cards, [
        'title' => 'Release',
        'description' => 'Review configuration',
        'completed' => true,
        'start_date' => '2026-11-10',
        'due_date' => '2026-11-20',
        'assignee_ids' => [$this->owner->id],
        'labels' => $labels,
        'checklists' => [[
            'id' => $checklistId,
            'title' => 'Verify',
            'items' => [['id' => $itemId, 'title' => 'Run tests', 'completed' => true]],
        ]],
    ])->assertCreated()->json('data');
    $this->postJson("/api/cards/{$source['id']}/comments", ['body' => 'Sample activity'])->assertCreated();
    $this->getJson($this->templates)->assertOk()->assertExactJson(['data' => []]);

    $template = $this->postJson($this->templates, ['name' => 'Release checklist', 'card_id' => $source['id']])
        ->assertCreated()->assertExactJsonStructure(['data' => ['id', 'name', 'title', 'description']])->json('data');
    $this->deleteJson("$this->cards/{$source['id']}")->assertNoContent();
    $this->getJson($this->templates)->assertOk()->assertExactJson(['data' => [$template]]);

    $first = $this->postJson("$this->cards/from-template", ['template_id' => $template['id']])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Release')
        ->assertJsonPath('data.description', 'Review configuration')
        ->assertJsonPath('data.labels', $labels)
        ->assertJsonPath('data.completed', false)
        ->assertJsonPath('data.assignee_ids', [])
        ->assertJsonPath('data.start_date', null)
        ->assertJsonPath('data.due_date', null)
        ->assertJsonPath('data.checklists.0.title', 'Verify')
        ->assertJsonPath('data.checklists.0.items.0.title', 'Run tests')
        ->assertJsonPath('data.checklists.0.items.0.completed', false)
        ->json('data');
    $second = $this->postJson("$this->cards/from-template", ['template_id' => $template['id']])
        ->assertCreated()->json('data');

    expect($first['checklists'][0]['id'])->not->toBe($checklistId)->not->toBe($second['checklists'][0]['id']);
    expect($first['checklists'][0]['items'][0]['id'])->not->toBe($itemId)
        ->not->toBe($second['checklists'][0]['items'][0]['id']);
    expect($second['position'])->toBeGreaterThan($first['position']);
    $this->getJson("/api/cards/{$first['id']}/activity")->assertOk()
        ->assertJsonPath('data.comments', [])->assertJsonPath('data.attachments', []);
});

it('allows members to list templates and enforces the cards.create permission on writes', function () {
    $source = $this->postJson($this->cards, ['title' => 'Card'])->assertCreated()->json('data.id');
    $template = $this->postJson($this->templates, ['name' => 'Template', 'card_id' => $source])
        ->assertCreated()->json('data.id');
    $this->postJson("$this->board/members", ['email' => $this->member->email, 'role' => 'viewer'])->assertCreated();
    $this->actingAs($this->member)->getJson($this->templates)->assertOk()->assertJsonPath('data.0.id', $template);
    $this->postJson($this->templates, ['name' => 'Template', 'card_id' => $source])->assertForbidden();
    $this->postJson("$this->cards/from-template", ['template_id' => $template])->assertForbidden();

    $this->actingAs($this->owner)->patchJson("$this->board/members/{$this->member->id}", ['role' => 'editor'])->assertOk();
    $this->actingAs($this->member)->postJson($this->templates, ['name' => 'Editor template', 'card_id' => $source])
        ->assertCreated();
    $this->postJson("$this->cards/from-template", ['template_id' => $template])
        ->assertCreated()->assertJsonPath('data.description', null)->assertJsonPath('data.checklists', []);
});

it('keeps templates and their source cards on their own board', function () {
    $source = $this->postJson($this->cards, ['title' => 'Card'])->assertCreated()->json('data.id');
    $template = $this->postJson($this->templates, ['name' => 'Template', 'card_id' => $source])
        ->assertCreated()->json('data.id');
    $anotherBoard = '/api/boards/'.$this->postJson('/api/boards', ['title' => 'Another'])->assertCreated()->json('data.id');
    $anotherColumn = $this->postJson("$anotherBoard/columns", ['title' => 'Inbox'])->assertCreated()->json('data.id');
    $this->postJson("$anotherBoard/card-templates", ['name' => 'Invalid', 'card_id' => $source])->assertNotFound();
    $this->postJson("/api/columns/$anotherColumn/cards/from-template", ['template_id' => $template])->assertNotFound();
    $this->getJson("/api/columns/$anotherColumn/cards")->assertOk()->assertExactJson(['data' => []]);

    $this->actingAs($this->member)->getJson($this->templates)->assertNotFound();
    $this->postJson($this->templates, ['name' => 'Template', 'card_id' => $source])->assertNotFound();
    $this->postJson("$this->cards/from-template", ['template_id' => $template])->assertNotFound();
});

it('validates template inputs and rejects deleted source cards', function () {
    $this->postJson($this->templates, ['name' => '', 'card_id' => '1'])
        ->assertUnprocessable()->assertJsonValidationErrors(['name', 'card_id']);
    $this->postJson("$this->cards/from-template", ['template_id' => -1])
        ->assertUnprocessable()->assertJsonValidationErrors('template_id');
    $this->postJson("$this->cards/from-template", ['template_id' => 1])->assertNotFound();

    $source = $this->postJson($this->cards, ['title' => 'Card'])->assertCreated()->json('data.id');
    $this->deleteJson("$this->cards/$source")->assertNoContent();
    $this->postJson($this->templates, ['name' => 'Template', 'card_id' => $source])->assertNotFound();
});
