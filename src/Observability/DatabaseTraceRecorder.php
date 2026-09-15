<?php

namespace Evolvex\AgentFabric\Observability;

use Evolvex\AgentFabric\Contracts\TraceRecorder;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final class DatabaseTraceRecorder implements TraceRecorder
{
    public function __construct(private readonly ConnectionInterface $db){}
    public function record(string $traceId,string $span,string $event,array $attributes=[],?float $durationMs=null): void{
        $this->db->table('ai_trace_spans')->insert(['id'=>(string)Str::uuid(),'trace_id'=>$traceId,'span'=>$span,'event'=>$event,'attributes'=>json_encode($attributes),'duration_ms'=>$durationMs,'created_at'=>now(),'updated_at'=>now()]);
    }
}
