<?php

namespace Evolvex\AgentFabric\Reliability;

use Evolvex\AgentFabric\Contracts\LeaseManager;
use Illuminate\Database\ConnectionInterface;

final class DatabaseLeaseManager implements LeaseManager
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function acquire(string $scope, string $key, string $owner, int $ttlSeconds = 30): bool
    {
        $now = now();
        $expires = now()->addSeconds(max(1, $ttlSeconds));
        $inserted = $this->db->table('ai_leases')->insertOrIgnore([
            'scope' => $scope, 'lease_key' => $key, 'owner' => $owner,
            'expires_at' => $expires, 'created_at' => $now, 'updated_at' => $now,
        ]);
        if ($inserted === 1) return true;
        return $this->db->table('ai_leases')
            ->where('scope', $scope)->where('lease_key', $key)
            ->where(function ($q) use ($now, $owner) { $q->where('expires_at', '<=', $now)->orWhere('owner', $owner); })
            ->update(['owner' => $owner, 'expires_at' => $expires, 'updated_at' => $now]) === 1;
    }

    public function renew(string $scope, string $key, string $owner, int $ttlSeconds = 30): bool
    {
        return $this->db->table('ai_leases')->where(compact('scope'))->where('lease_key', $key)->where('owner', $owner)
            ->update(['expires_at' => now()->addSeconds(max(1, $ttlSeconds)), 'updated_at' => now()]) === 1;
    }

    public function release(string $scope, string $key, string $owner): void
    {
        $this->db->table('ai_leases')->where(compact('scope'))->where('lease_key', $key)->where('owner', $owner)->delete();
    }

    public function owner(string $scope, string $key): ?string
    {
        $row = $this->db->table('ai_leases')->where(compact('scope'))->where('lease_key', $key)->where('expires_at', '>', now())->first();
        return $row ? (string) $row->owner : null;
    }
}
