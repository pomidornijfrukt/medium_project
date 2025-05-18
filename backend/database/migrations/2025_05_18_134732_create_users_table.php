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
        Schema::create('users', function (Blueprint $table) {
            $table->string('UID', 36)->primary(); // UUID example
            $table->string('Username')->unique();
            $table->string('Email')->unique();
            $table->string('Password');
            $table->string('Role', 50);
            $table->timestamp('LastLoginAt')->nullable();
            $table->enum('Status', ['active', 'banned', 'pending'])->default('pending');
            $table->timestamps();

            $table->foreign('Role')
                ->references('Role Name')
                ->on('roles')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
