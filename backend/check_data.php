<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

// Set up application context
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Checking Default Data ===\n\n";

// Check roles
echo "Roles in database:\n";
$roles = DB::table('roles')->get();
foreach ($roles as $role) {
    echo "- {$role->RoleName}: {$role->RoleDescription}\n";
}
echo "Total: " . $roles->count() . " roles\n\n";

// Check tags  
echo "Tags in database:\n";
$tags = DB::table('tags')->get();
foreach ($tags as $tag) {
    echo "- {$tag->TagName}: {$tag->Description}\n";
}
echo "Total: " . $tags->count() . " tags\n\n";

echo "Done!\n";
