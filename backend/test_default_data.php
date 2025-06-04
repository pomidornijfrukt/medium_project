<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Request::capture();
$response = $kernel->handle($request);

// Test the database setup
echo "=== Testing Default Roles and Tags ===\n\n";

try {
    // Test Roles
    echo "Checking Roles:\n";
    $roles = App\Models\Role::all();
    foreach ($roles as $role) {
        echo "- {$role->RoleName}: {$role->RoleDescription}\n";
    }
    echo "\nTotal roles: " . $roles->count() . "\n\n";

    // Test Tags
    echo "Checking Tags:\n";
    $tags = App\Models\Tag::all();
    foreach ($tags as $tag) {
        echo "- {$tag->TagName}: {$tag->Description}\n";
    }
    echo "\nTotal tags: " . $tags->count() . "\n\n";

    // Test auto-fill functionality by creating a new tag without description
    echo "Testing auto-fill functionality:\n";
    $testTag = App\Models\Tag::create([
        'TagName' => 'test-auto-fill'
    ]);
    echo "Created tag '{$testTag->TagName}' with auto-filled description: '{$testTag->Description}'\n\n";

    // Clean up test tag
    $testTag->delete();
    echo "Test tag cleaned up.\n\n";

    // Test creating a tag with custom description
    echo "Testing custom description:\n";
    $customTag = App\Models\Tag::create([
        'TagName' => 'custom-tag',
        'Description' => 'This is a custom description'
    ]);
    echo "Created tag '{$customTag->TagName}' with custom description: '{$customTag->Description}'\n\n";

    // Clean up custom tag
    $customTag->delete();
    echo "Custom tag cleaned up.\n\n";

    echo "✅ All tests passed successfully!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
