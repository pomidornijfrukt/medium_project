<?php

require_once 'vendor/autoload.php';

try {
    $app = require_once 'bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    echo "Testing auto-fill...\n";
    
    // Simple test
    $tag = new App\Models\Tag();
    $tag->TagName = 'test-simple';
    $tag->save();
    
    echo "Tag created: {$tag->TagName} - {$tag->Description}\n";
    $tag->delete();
    echo "Test completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
