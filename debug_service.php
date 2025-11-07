<?php
require 'tests/bootstrap.php';

use App\Service\SportConfigService;

$service = new SportConfigService();

// Get the defaults directly via reflection
$reflection = new ReflectionClass($service);
$defaultsProp = $reflection->getProperty('defaults');
$defaultsProp->setAccessible(true);
$defaults = $defaultsProp->getValue($service);

echo "Defaults structure:\n";
print_r($defaults);

echo "\nTesting basketball config:\n";
$sportName = 'basketball';
$key = 'statTables';

echo "Looking for config: {$sportName}.{$key}\n";

if (isset($defaults[$sportName])) {
    echo "Found sport: {$sportName}\n";
    if (isset($defaults[$sportName][$key])) {
        echo "Found key: {$key}\n";
        print_r($defaults[$sportName][$key]);
    } else {
        echo "Key {$key} not found in sport config\n";
        echo "Available keys: " . implode(', ', array_keys($defaults[$sportName])) . "\n";
    }
} else {
    echo "Sport {$sportName} not found\n";
    echo "Available sports: " . implode(', ', array_keys($defaults)) . "\n";
}
