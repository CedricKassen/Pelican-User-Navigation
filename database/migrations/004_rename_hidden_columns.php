<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "hidden" clashes with the $hidden property of eloquent models, so the attribute couldn't be set from within the models.
return new class extends Migration
{
    public function up(): void
    {
        foreach (['user_navigation_preferences', 'user_navigation_defaults'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('hidden', 'hidden_items');
            });
        }
    }

    public function down(): void
    {
        foreach (['user_navigation_preferences', 'user_navigation_defaults'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('hidden_items', 'hidden');
            });
        }
    }
};
