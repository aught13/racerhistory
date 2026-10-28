<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Cache\Cache;

class CacheEngineService
{
    /**
     * @return array<int,string>
     */
    public function configured(): array
    {
        return Cache::configured();
    }

    /**
     * Clear the named cache configuration.
     *
     * @param string $name The cache configuration name.
     * @return bool
     */
    public function clear(string $name): bool
    {
        return Cache::clear($name);
    }
}
