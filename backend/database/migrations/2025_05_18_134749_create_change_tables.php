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
            $table->string('OldUserName');
            $table->string('NewUserName');
            $table->timestamps();
        });

        // Email Changes
        Schema::create('email_changes', function (Blueprint $table) {
            $table->id('EmailChangeID');
            $table->string('OldEmail');
            $table->string('NewEmail');
            $table->timestamps();
        });

        // Password Changes
        Schema::create('password_changes', function (Blueprint $table) {
            $table->id('PassChangeID');
            $table->string('OldPasswordHash');
            $table->string('NewPasswordHash');
            $table->timestamps();
        });

        // Role Changes
        Schema::create('role_changes', function (Blueprint $table) {
            $table->id('RoleChangeID');
            $table->string('OldRoleID', 50);
            $table->string('NewRoleID', 50);
            $table->timestamps();

            $table->foreign('OldRoleID')
                ->references('RoleName')
                ->on('roles')
                ->onDelete('cascade');

            $table->foreign('NewRoleID')
                ->references('RoleName')
                ->on('roles')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_changes');
        Schema::dropIfExists('password_changes');
        Schema::dropIfExists('email_changes');
        Schema::dropIfExists('user_name_changes');
    }
};
