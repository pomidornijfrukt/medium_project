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
        Schema::create('tag_is_used', function (Blueprint $table) {
            $table->id('UniqueID');
            $table->string('TagName');
            $table->unsignedBigInteger('Post_ID');

            $table->foreign('TagName')->references('TagName')->on('tags');
            $table->foreign('Post_ID')->references('PostID')->on('posts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tag_is_used');
    }
};
