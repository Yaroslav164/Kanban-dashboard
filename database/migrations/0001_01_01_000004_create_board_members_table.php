<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_members', function (Blueprint $table) {
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['editor', 'viewer']);
            $table->primary(['board_id', 'user_id']);
            $table->index(['user_id', 'board_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_members');
    }
};
