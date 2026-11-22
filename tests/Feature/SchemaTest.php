<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('uses double precision ordering and indexes the active board hierarchy', function () {
    $columnTypes = DB::table('information_schema.columns')
        ->where('table_schema', DB::raw('current_schema()'))
        ->whereIn('table_name', ['columns', 'cards'])
        ->where('column_name', 'position')
        ->pluck('data_type', 'table_name');

    expect($columnTypes->all())->toMatchArray([
        'columns' => 'integer',
        'cards' => 'double precision',
    ]);

    $indexDefinitions = DB::table('pg_indexes')
        ->where('schemaname', DB::raw('current_schema()'))
        ->whereIn('indexname', ['boards_active_order', 'columns_active_order', 'cards_active_order'])
        ->pluck('indexdef', 'indexname')
        ->map(fn (string $definition): string => str_replace('"', '', $definition));

    expect($indexDefinitions)->toHaveCount(3)
        ->and($indexDefinitions['boards_active_order'])->toContain('(user_id, id)', 'WHERE (deleted_at IS NULL)')
        ->and($indexDefinitions['columns_active_order'])->toContain('(board_id, position, id)', 'WHERE (deleted_at IS NULL)')
        ->and($indexDefinitions['cards_active_order'])->toContain('(column_id, position, id)', 'WHERE (deleted_at IS NULL)');
});

it('rejects a board with a missing owner', function () {
    expect(fn () => DB::table('boards')->insert([
        'user_id' => -1,
        'title' => 'Orphan',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class, '23503');
});

it('allows signups without a GitHub identity and rejects duplicate GitHub identities', function () {
    DB::table('users')->insert([
        ['name' => 'One', 'email' => 'one@example.test', 'github_id' => null],
        ['name' => 'Two', 'email' => 'two@example.test', 'github_id' => null],
        ['name' => 'Three', 'email' => 'three@example.test', 'github_id' => 12345],
    ]);

    expect(fn () => DB::table('users')->insert([
        'name' => 'Four',
        'email' => 'four@example.test',
        'github_id' => 12345,
    ]))->toThrow(QueryException::class, '23505');
});
