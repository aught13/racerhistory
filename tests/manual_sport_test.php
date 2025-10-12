<?php
declare(strict_types=1);

/**
 * Multi-Sport Configuration Test Script
 *
 * Quick manual test to verify sport configurations are working
 * Run with: php bin/cake.php console < tests/manual_sport_test.php
 */

use Cake\Datasource\ConnectionManager;

echo "=== Multi-Sport Configuration Test ===\n\n";

// Test 1: Check sport_configs table data
echo "1. Testing sport configurations in database:\n";
$connection = ConnectionManager::get('default');
$query = $connection->newQuery()
    ->select(['sport_id', 'config_key', 'config_value'])
    ->from('sport_configs')
    ->where(['sport_id IN' => [1, 11]]) // Basketball and Football
    ->orderBy(['sport_id', 'config_key']);

$results = $query->execute()->fetchAll('assoc');

foreach ($results as $row) {
    echo "  Sport {$row['sport_id']}: {$row['config_key']} = {$row['config_value']}\n";
}

echo "\n2. Testing GameEav template generation:\n";

// Load GameEav table
$gameEav = \Cake\ORM\TableRegistry::getTableLocator()->get('GameEav');

// Test Basketball - 2 periods (halves)
echo "\n--- Basketball 2 Periods (Halves) ---\n";
try {
    $template = $gameEav->getEavTemplateForSport(1, '2', '0');
    foreach ($template as $key => $config) {
        if (str_starts_with($key, 'period_') || str_starts_with($key, 'official_')) {
            echo "  {$key}: {$config['label']}\n";
        }
    }
} catch (Exception $e) {
    echo '  Error: ' . $e->getMessage() . "\n";
}

// Test Basketball - 4 periods (quarters)
echo "\n--- Basketball 4 Periods (Quarters) ---\n";
try {
    $template = $gameEav->getEavTemplateForSport(1, '4', '1');
    foreach ($template as $key => $config) {
        if (str_starts_with($key, 'period_') || str_starts_with($key, 'overtime_')) {
            echo "  {$key}: {$config['label']}\n";
            if ($key === 'period_4_opponent') {
                break; // Limit output
            }
        }
    }
} catch (Exception $e) {
    echo '  Error: ' . $e->getMessage() . "\n";
}

// Test Football
echo "\n--- Football 4 Periods (Quarters) ---\n";
try {
    $template = $gameEav->getEavTemplateForSport(11, '4', '0');
    $periodKeys = array_filter(array_keys($template), fn($k) => str_starts_with($k, 'period_'));
    foreach (array_slice($periodKeys, 0, 4) as $key) {
        echo "  {$key}: {$template[$key]['label']}\n";
    }
} catch (Exception $e) {
    echo '  Error: ' . $e->getMessage() . "\n";
}

echo "\n3. Testing EAV attribute saving:\n";
try {
    $testGameId = 999; // Use a high ID that won't conflict
    $testData = [
        'period_1_team' => '25',
        'period_1_opponent' => '20',
        'period_2_team' => '30',
        'period_2_opponent' => '25',
        'official_1' => 'Test Referee',
    ];

    $result = $gameEav->saveBulkAttributes($testGameId, $testData);
    echo '  Bulk save result: ' . ($result ? 'SUCCESS' : 'FAILED') . "\n";

    $retrieved = $gameEav->getAttributesForGame($testGameId);
    echo '  Retrieved attributes: ' . count($retrieved) . " items\n";

    // Clean up test data
    $gameEav->deleteAll(['game_id' => $testGameId]);
    echo "  Test data cleaned up\n";
} catch (Exception $e) {
    echo '  Error: ' . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
