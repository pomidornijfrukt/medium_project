<?php
// Test script to check database tables and relationships

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

try {
    echo "=== Database Table Check ===\n";
    
    // Check if tables exist
    $tables = ['posts', 'tags', 'tag_is_used'];
    foreach ($tables as $table) {
        $exists = Schema::hasTable($table);
        echo "Table '$table': " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "\n";
    }
    
    echo "\n=== Testing Post-Tag Relationship ===\n";
    
    // Create a test tag if none exists
    $tag = Tag::firstOrCreate(['TagName' => 'Laravel']);
    echo "Tag 'Laravel' exists: ✅\n";
    
    // Test fetching posts with tags (this is what's failing)
    echo "Testing Post::with(['author', 'tags']) query...\n";
    
    try {
        $posts = Post::with(['author', 'tags'])->get();
        echo "✅ Query successful! Found " . $posts->count() . " posts\n";
        
        foreach ($posts as $post) {
            echo "- Post: " . $post->Topic . " (Tags: " . $post->tags->count() . ")\n";
        }
    } catch (Exception $e) {
        echo "❌ Query failed: " . $e->getMessage() . "\n";
        echo "Full error: " . $e->getTraceAsString() . "\n";
    }
    
    echo "\n=== Table Structure Check ===\n";
    
    // Check tag_is_used table structure
    if (Schema::hasTable('tag_is_used')) {
        $columns = Schema::getColumnListing('tag_is_used');
        echo "tag_is_used columns: " . implode(', ', $columns) . "\n";
        
        $count = DB::table('tag_is_used')->count();
        echo "tag_is_used rows: $count\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
