<?php

namespace NewGeode\WebGuard\Support;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class NGStore
{

    // Get the configured cache store for WebGuard.
    public function store(): Repository {
        return Cache::store(config('webguard.cache_store', 'file'));
    }

    // Increment global requests-per-minute (RPM) bucket.
    public function incrementRpm(string $bucketKey, int $ttlSeconds = 600): int {

        $cache = $this->store();

        if(!$cache->has($bucketKey)){
            $cache->put($bucketKey, 1, $ttlSeconds);
            return 1;
        }

        return (int) $cache->increment($bucketKey);
    }

    // Increment rate limit counter for an IP or User ID within a rolling window.
    public function hitRateLimit(string $key, int $decaySeconds = 60): int {

        $cache = $this->store();

        if(!$cache->has($key)){
            $cache->put($key, 1, $decaySeconds);
            return 1;
        }

        return (int) $cache->increment($key);
    }

    // Temporarily ban an IP address.
    public function banIp(string $ip, int $minutes = 30): void {

        $this->store()->put(
            $this->banKey($ip),
            true,
            now()->addMinutes($minutes)
        );
    }

    // Unban an IP address manually (useful for Artisan commands).
    public function unbanIp(string $ip): void {
        $this->store()->forget($this->banKey($ip));
    }

    // Check if an IP address is currently in temporary jail.
    public function isBanned(string $ip): bool {
        return (bool) $this->store()->get($this->banKey($ip), false);
    }

    // Activate Panic Shield mode for a set duration.
    public function activatePanic(int $minutes = 10): void {
        $this->store()->put(
            'webguard:panic_mode',
            true,
            now()->addMinutes($minutes)
        );
    }

    // Check if Panic Shield mode is currently active.
    public function isPanicActive(): bool {
        return (bool) $this->store()->get('webguard:panic_mode', false);
    }

    // Generate a unique cache key for banning an IP address.
    protected function banKey(string $ip): string {
        return 'webguard:ban:'.sha1($ip);
    }
}