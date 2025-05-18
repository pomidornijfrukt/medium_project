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
        Schema::create('actions', function (Blueprint $table) {
            $table->id('ActionID');
            $table->string('Author', 36);
            $table->string('Victim', 36);
            $table->dateTime('Action DateTime');
            $table->foreignId('UserNameChangeID')->nullable();
            $table->foreignId('EmailChangeID')->nullable();
            $table->foreignId('PassChangeID')->nullable();
            $table->foreignId('RoleChangeID')->nullable();
            $table->timestamps();

            // Relationships
            $table->foreign('Author')
                ->references('UID')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('Victim')
                ->references('UID')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('UserNameChangeID')
                ->references('UserNameChangeID')
                ->on('user_name_changes')
                ->onDelete('cascade');

            $table->foreign('EmailChangeID')
                ->references('EmailChangeID')
                ->on('email_changes')
                ->onDelete('cascade');

            $table->foreign('PassChangeID')
                ->references('PassChangeID')
                ->on('password_changes')
                ->onDelete('cascade');

            $table->foreign('RoleChangeID')
                ->references('RoleChangeID')
                ->on('role_changes')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actions');
    }
};
