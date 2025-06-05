<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes to improve performance
        Schema::table('users', function (Blueprint $table) {
            $table->index('Email', 'users_email_index');
            $table->index('Status', 'users_status_index');
            $table->index('Role', 'users_role_index');
            $table->index(['Status', 'Role'], 'users_status_role_index');
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->index('tokenable_id', 'pat_tokenable_id_index');
            $table->index(['tokenable_type', 'tokenable_id'], 'pat_tokenable_index');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->index('Author', 'posts_author_index');
            $table->index('Status', 'posts_status_index');
            $table->index(['Status', 'Author'], 'posts_status_author_index');
            $table->index('created_at', 'posts_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_email_index');
            $table->dropIndex('users_status_index');
            $table->dropIndex('users_role_index');
            $table->dropIndex('users_status_role_index');
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex('pat_tokenable_id_index');
            $table->dropIndex('pat_tokenable_index');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_author_index');
            $table->dropIndex('posts_status_index');
            $table->dropIndex('posts_status_author_index');
            $table->dropIndex('posts_created_at_index');
        });
    }
};
