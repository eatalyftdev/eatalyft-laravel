<?php
echo "PHP version: " . PHP_VERSION . "\n";
echo "Current dir: " . __DIR__ . "\n";

$file = __DIR__ . '/vendor/laravel/sanctum/src/HasApiTokens.php';
echo "File exists: " . (file_exists($file) ? 'yes' : 'no') . "\n";

if (file_exists($file)) {
    $content = file_get_contents($file);
    echo "File size: " . strlen($content) . "\n";
    echo "First 100 chars: " . substr($content, 0, 100) . "\n";
}

include $file;
echo "After include, class_exists: " . (class_exists('Laravel\Sanctum\HasApiTokens') ? 'yes' : 'no') . "\n";
echo "trait_exists: " . (trait_exists('Laravel\Sanctum\HasApiTokens') ? 'yes' : 'no') . "\n";