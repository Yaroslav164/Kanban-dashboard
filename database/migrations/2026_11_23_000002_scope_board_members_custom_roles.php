<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('board_roles', function (Blueprint $table) {
            $table->unique(['board_id', 'id']);
        });

        Schema::table('board_members', function (Blueprint $table) {
            $table->foreign(['board_id', 'custom_role_id'])->references(['board_id', 'id'])->on('board_roles');
            $table->dropForeign(['custom_role_id']);
        });
    }

    public function down(): void
    {
        Schema::table('board_members', function (Blueprint $table) {
            $table->foreign('custom_role_id')->references('id')->on('board_roles')->restrictOnDelete();
            $table->dropForeign(['board_id', 'custom_role_id']);
        });

        Schema::table('board_roles', function (Blueprint $table) {
            $table->dropUnique(['board_id', 'id']);
        });
    }
};
