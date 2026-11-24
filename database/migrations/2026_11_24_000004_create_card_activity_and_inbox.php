<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('cards')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 32);
            $table->string('detail')->nullable();
            $table->timestamp('created_at');
            $table->index(['card_id', 'id']);
        });

        Schema::create('board_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('card_id')->constrained('cards')->cascadeOnDelete();
            $table->string('kind', 32);
            $table->string('source', 64);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at');
            $table->unique(['user_id', 'card_id', 'kind', 'source'], 'board_notification_source_unique');
            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_notifications');
        Schema::dropIfExists('card_history');
    }
};
