<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Debug route for Swagger
Route::get('/debug-swagger', function () {
    $documentation = 'default';
    $urlsToDocs = [
        'Forum API' => route('l5-swagger.default.docs') . '/api-docs.json'
    ];
    $documentationTitle = 'Forum API Documentation';
    
    return view('l5-swagger::index', compact('documentation', 'urlsToDocs', 'documentationTitle'));
});

// Test route to check basic functionality
Route::get('/test-docs', function () {
    return 'Test route is working!';
});

// Test SwaggerController functionality manually
Route::get('/test-swagger-controller', function () {
    try {
        $configFactory = app('L5Swagger\ConfigFactory');
        $documentation = 'default';
        $config = $configFactory->documentationConfig($documentation);
        
        // Mimic what SwaggerController@api does
        $urlToDocs = route('l5-swagger.default.docs', 'api-docs.json');
        $urlsToDocs = [$config['api']['title'] => $urlToDocs];
        $useAbsolutePath = $config['paths']['use_absolute_path'] ?? true;
        
        return view('l5-swagger::index', [
            'documentation' => $documentation,
            'documentationTitle' => $config['api']['title'] ?? $documentation,
            'secure' => request()->secure(),
            'urlToDocs' => $urlToDocs,
            'urlsToDocs' => $urlsToDocs,
            'operationsSorter' => $config['operations_sort'] ?? null,
            'configUrl' => $config['additional_config_url'] ?? null,
            'validatorUrl' => $config['validator_url'] ?? null,
            'useAbsolutePath' => $useAbsolutePath,
        ]);
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine();
    }
});
