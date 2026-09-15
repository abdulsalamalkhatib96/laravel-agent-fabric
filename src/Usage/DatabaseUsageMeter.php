<?php

namespace Evolvex\AgentFabric\Usage;

use Evolvex\AgentFabric\Contracts\UsageMeter;
use Evolvex\AgentFabric\Data\ModelResponse;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final class DatabaseUsageMeter implements UsageMeter
{
    public function __construct(private readonly ConnectionInterface $db) {}
    public function record(string $runId, ModelResponse $response): void
    {
        $this->db->table('ai_usage')->insert([
            'id'=>(string)Str::uuid(),'run_id'=>$runId,'provider'=>$response->provider,'model'=>$response->model,
            'input_tokens'=>$response->inputTokens,'output_tokens'=>$response->outputTokens,'cost'=>$response->cost ?? 0,
            'metadata'=>json_encode($response->metadata),'created_at'=>now(),'updated_at'=>now(),
        ]);
    }
    public function cost(string $runId): float { return (float) $this->db->table('ai_usage')->where('run_id',$runId)->sum('cost'); }
}
