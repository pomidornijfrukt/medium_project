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
        Schema::create('tag_is_used', function (Blueprint $table) {
            $table->id('UniqueID');
            $table->string('TagName', 50);
            $table->unsignedBigInteger('PostID');
            $table->timestamps();

            $table->foreign('TagName')
                ->references('TagName')
                ->on('tags')
                ->onDelete('cascade');

            $table->foreign('PostID')
                ->references('PostID')
                ->on('posts')
                ->onDelete('cascade');

            $table->unique(['TagName', 'PostID']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tag_is_used');
    }
};
