<?php

namespace Evolvex\AgentFabric\Reliability;

use Evolvex\AgentFabric\Contracts\CircuitBreaker;
use Illuminate\Database\ConnectionInterface;

final class DatabaseCircuitBreaker implements CircuitBreaker
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function allow(string $key): bool
    {
        $row=$this->db->table('ai_circuit_breakers')->where('breaker_key',$key)->first();
        if(!$row)return true;
        if($row->state==='closed')return true;
        if($row->state==='open' && $row->opened_until && strtotime((string)$row->opened_until) <= time()){
            $this->db->table('ai_circuit_breakers')->where('breaker_key',$key)->update(['state'=>'half_open','updated_at'=>now()]); return true;
        }
        return $row->state==='half_open';
    }
    public function success(string $key): void
    {
        $this->db->table('ai_circuit_breakers')->updateOrInsert(['breaker_key'=>$key],['state'=>'closed','failures'=>0,'last_failure'=>null,'opened_until'=>null,'updated_at'=>now(),'created_at'=>now()]);
    }
    public function failure(string $key, ?string $reason = null): void
    {
        $threshold=(int)config('agent-fabric.reliability.circuit_breaker.failure_threshold',5);
        $cooldown=(int)config('agent-fabric.reliability.circuit_breaker.cooldown_seconds',60);
        $row=$this->db->table('ai_circuit_breakers')->where('breaker_key',$key)->first();
        $failures=(int)($row->failures??0)+1;
        $this->db->table('ai_circuit_breakers')->updateOrInsert(['breaker_key'=>$key],[
            'state'=>$failures >= $threshold ? 'open' : 'closed','failures'=>$failures,'last_failure'=>$reason,
            'opened_until'=>$failures >= $threshold ? now()->addSeconds($cooldown) : null,'updated_at'=>now(),'created_at'=>$row?->created_at??now(),
        ]);
    }
    public function state(string $key): string { return (string)($this->db->table('ai_circuit_breakers')->where('breaker_key',$key)->value('state')??'closed'); }
}
