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
        Schema::create('posts', function (Blueprint $table) {
            $table->id('PostID');
            $table->string('Author', 36);
            $table->string('Topic');
            $table->text('Content');
            $table->enum('Status', ['draft', 'published', 'deleted'])->default('draft');
            $table->timestamp('LastEditedAt')->nullable();
            $table->timestamps();

            $table->foreign('Author')
                ->references('UID')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
