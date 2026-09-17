<?php

namespace Evolvex\AgentFabric\Quotas;

use Evolvex\AgentFabric\Contracts\QuotaManager;
use Illuminate\Database\ConnectionInterface;

final class DatabaseQuotaManager implements QuotaManager
{
    public function __construct(private readonly ConnectionInterface $db) {}
    public function consume(string $scope, string $key, int $amount = 1, ?int $limit = null, ?int $windowSeconds = null): bool
    {
        $limit ??= (int) config("agent-fabric.quotas.{$scope}.limit", PHP_INT_MAX);
        $windowSeconds ??= (int) config("agent-fabric.quotas.{$scope}.window_seconds", 60);
        $bucket=(int)(floor(time()/max(1,$windowSeconds))*max(1,$windowSeconds));
        $this->db->table('ai_quota_counters')->insertOrIgnore(['scope'=>$scope,'quota_key'=>$key,'bucket'=>$bucket,'consumed'=>0,'created_at'=>now(),'updated_at'=>now()]);
        $updated=$this->db->table('ai_quota_counters')->where(compact('scope'))->where('quota_key',$key)->where('bucket',$bucket)->where('consumed','<=',$limit-$amount)->update(['consumed'=>$this->db->raw('consumed + '.max(0,$amount)),'updated_at'=>now()]);
        return $updated===1;
    }
    public function remaining(string $scope, string $key, ?int $limit = null, ?int $windowSeconds = null): int
    {
        $limit ??= (int) config("agent-fabric.quotas.{$scope}.limit", PHP_INT_MAX);
        $windowSeconds ??= (int) config("agent-fabric.quotas.{$scope}.window_seconds", 60);
        $bucket=(int)(floor(time()/max(1,$windowSeconds))*max(1,$windowSeconds));
        $used=(int)($this->db->table('ai_quota_counters')->where(compact('scope'))->where('quota_key',$key)->where('bucket',$bucket)->value('consumed')??0);
        return max(0,$limit-$used);
    }
}
