<?php

use App\Models\User;
use App\Services\UnitOfWork;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rolls back when the service rejects or fails to confirm a command', function (bool $explicit) {
    expect(function () use ($explicit) {
        (new UnitOfWork)->run(function ($transaction) use ($explicit) {
            User::query()->create(['name' => 'Transaction', 'email' => 'rollback@example.test']);
            if ($explicit) {
                $transaction->rollback(new RuntimeException('Rejected'));
            }
        });
    })->toThrow($explicit ? RuntimeException::class : LogicException::class);
    $this->assertDatabaseMissing('users', ['email' => 'rollback@example.test']);
})->with([true, false]);
