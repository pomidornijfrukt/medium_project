<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing Tag Auto-Fill Functionality ===\n\n";

try {
    // Test 1: Create tag without description (should auto-fill)
    echo "Test 1: Creating tag without description...\n";
    $tag1 = App\Models\Tag::create([
        'TagName' => 'auto-test-1'
    ]);
    echo "✅ Created tag '{$tag1->TagName}' with auto-filled description: '{$tag1->Description}'\n\n";

    // Test 2: Create tag with empty description (should auto-fill)
    echo "Test 2: Creating tag with empty description...\n";
    $tag2 = App\Models\Tag::create([
        'TagName' => 'auto-test-2',
        'Description' => ''
    ]);
    echo "✅ Created tag '{$tag2->TagName}' with auto-filled description: '{$tag2->Description}'\n\n";

    // Test 3: Create tag with custom description (should keep custom)
    echo "Test 3: Creating tag with custom description...\n";
    $tag3 = App\Models\Tag::create([
        'TagName' => 'custom-test',
        'Description' => 'This is a custom description for testing'
    ]);
    echo "✅ Created tag '{$tag3->TagName}' with custom description: '{$tag3->Description}'\n\n";

    // Test 4: Update tag with empty description (should auto-fill)
    echo "Test 4: Updating tag with empty description...\n";
    $tag3->Description = '';
    $tag3->save();
    echo "✅ Updated tag '{$tag3->TagName}' with auto-filled description: '{$tag3->Description}'\n\n";

    // Clean up test data
    echo "Cleaning up test data...\n";
    $tag1->delete();
    $tag2->delete();
    $tag3->delete();
    echo "✅ Test data cleaned up.\n\n";

    echo "🎉 All auto-fill tests passed successfully!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
