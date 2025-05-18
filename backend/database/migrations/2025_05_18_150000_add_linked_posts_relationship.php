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
        Schema::table('posts', function (Blueprint $table) {
            // Add ParentPostID column for linking posts as "comments"
            $table->unsignedBigInteger('ParentPostID')->nullable();
            
            // Add foreign key constraint
            $table->foreign('ParentPostID')
                ->references('PostID')
                ->on('posts')
                ->onDelete('cascade');
                
            // Add column to indicate the type of post (main post or linked post/comment)
            $table->enum('PostType', ['main', 'linked'])->default('main');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['ParentPostID']);
            $table->dropColumn('ParentPostID');
            $table->dropColumn('PostType');
        });
    }
};
