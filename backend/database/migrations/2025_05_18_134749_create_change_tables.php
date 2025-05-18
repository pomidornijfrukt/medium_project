<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        // UserName Changes
        Schema::create('user_name_changes', function (Blueprint $table) {
            $table->id('UserNameChangeID');
            $table->string('Old UserName');
            $table->string('New UserName');
            $table->timestamps();
        });

        // Email Changes
        Schema::create('email_changes', function (Blueprint $table) {
            $table->id('EmailChangeID');
            $table->string('Old Email');
            $table->string('New Email');
            $table->timestamps();
        });

        // Password Changes
        Schema::create('password_changes', function (Blueprint $table) {
            $table->id('PassChangeID');
            $table->string('Old Password Hash');
            $table->string('New Password Hash');
            $table->timestamps();
        });

        // Role Changes
        Schema::create('role_changes', function (Blueprint $table) {
            $table->id('RoleChangeID');
            $table->string('Old Role ID', 50);
            $table->string('New Role ID', 50);
            $table->timestamps();

            $table->foreign('Old Role ID')
                ->references('Role Name')
                ->on('roles')
                ->onDelete('cascade');

            $table->foreign('New Role ID')
                ->references('Role Name')
                ->on('roles')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('change_tables');
    }
};
