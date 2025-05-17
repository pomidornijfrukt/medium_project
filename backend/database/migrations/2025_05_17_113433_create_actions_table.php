<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('actions', function (Blueprint $table) {
            $table->id('ActionID');
            $table->unsignedBigInteger('Author');
            $table->unsignedBigInteger('Victim');
            $table->timestamp('Action_DateTime');
            $table->unsignedBigInteger('UserNameChangeID')->nullable();
            $table->unsignedBigInteger('EmailChangeID')->nullable();
            $table->unsignedBigInteger('PassChangeID')->nullable();
            $table->unsignedBigInteger('RoleChangeID')->nullable();

            $table->foreign('Author')->references('UID')->on('users');
            $table->foreign('Victim')->references('UID')->on('users');
            $table->foreign('UserNameChangeID')->references('UserNameChangeID')->on('user_name_changes');
            $table->foreign('EmailChangeID')->references('EmailChangeID')->on('email_changes');
            $table->foreign('PassChangeID')->references('PassChangeID')->on('pass_changes');
            $table->foreign('RoleChangeID')->references('RoleChangeID')->on('role_changes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('actions');
    }
};
