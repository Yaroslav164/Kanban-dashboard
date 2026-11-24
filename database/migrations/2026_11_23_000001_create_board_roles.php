<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->json('permissions');
            $table->unique(['board_id', 'name']);
        });

        Schema::table('board_members', function (Blueprint $table) {
            $table->foreignId('custom_role_id')->nullable()->constrained('board_roles')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('board_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('custom_role_id');
        });
        Schema::dropIfExists('board_roles');
    }
};
