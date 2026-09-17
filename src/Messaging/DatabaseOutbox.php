<?php

namespace Evolvex\AgentFabric\Messaging;

use Evolvex\AgentFabric\Contracts\Outbox;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final class DatabaseOutbox implements Outbox
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function add(string $topic, array $payload, ?string $deduplicationKey = null, ?string $tenantId = null, ?string $availableAt = null): string
    {
        $id = (string) Str::uuid();
        $key = $deduplicationKey ?: hash('sha256', $topic.'|'.json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        $this->db->table('ai_outbox')->insertOrIgnore([
            'id' => $id, 'tenant_id' => $tenantId, 'topic' => $topic, 'payload' => json_encode($payload),
            'deduplication_key' => $key, 'status' => 'pending', 'attempts' => 0,
            'available_at' => $availableAt ? date('Y-m-d H:i:s', strtotime($availableAt)) : now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $row = $this->db->table('ai_outbox')->where('deduplication_key', $key)->first();
        return (string) ($row->id ?? $id);
    }

    public function claim(string $worker, int $limit = 100, int $leaseSeconds = 60): array
    {
        $ids = $this->db->table('ai_outbox')->whereIn('status', ['pending','failed'])
            ->where('available_at', '<=', now())
            ->where(function ($q) { $q->whereNull('lease_expires_at')->orWhere('lease_expires_at', '<=', now()); })
            ->orderBy('created_at')->limit(max(1, $limit))->pluck('id')->all();
        $claimed = [];
        foreach ($ids as $id) {
            $updated = $this->db->table('ai_outbox')->where('id', $id)
                ->where(function ($q) { $q->whereNull('lease_expires_at')->orWhere('lease_expires_at', '<=', now()); })
                ->update(['status' => 'processing', 'lease_owner' => $worker, 'lease_expires_at' => now()->addSeconds($leaseSeconds), 'attempts' => $this->db->raw('attempts + 1'), 'updated_at' => now()]);
            if ($updated === 1) $claimed[] = (array) $this->db->table('ai_outbox')->where('id', $id)->first();
        }
        return $claimed;
    }

    public function acknowledge(string $id, string $worker): void
    {
        $this->db->table('ai_outbox')->where('id', $id)->where('lease_owner', $worker)->update(['status'=>'delivered','delivered_at'=>now(),'lease_owner'=>null,'lease_expires_at'=>null,'updated_at'=>now()]);
    }

    public function fail(string $id, string $worker, string $message, int $retryAfterSeconds = 60): void
    {
        $this->db->table('ai_outbox')->where('id', $id)->where('lease_owner', $worker)->update(['status'=>'failed','last_error'=>$message,'available_at'=>now()->addSeconds(max(1,$retryAfterSeconds)),'lease_owner'=>null,'lease_expires_at'=>null,'updated_at'=>now()]);
    }
}
