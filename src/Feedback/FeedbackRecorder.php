<?php

namespace Evolvex\AgentFabric\Feedback;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final class FeedbackRecorder
{
    public function __construct(private readonly ConnectionInterface $db){}
    public function record(string $runId,string|int $tenantId,?int $rating=null,?string $label=null,?string $reason=null,?string $correctedAnswer=null):string{$id=(string)Str::uuid();$this->db->table('ai_feedback')->insert(['id'=>$id,'run_id'=>$runId,'tenant_id'=>(string)$tenantId,'rating'=>$rating,'label'=>$label,'reason'=>$reason,'corrected_answer'=>$correctedAnswer,'created_at'=>now(),'updated_at'=>now()]);return $id;}
}
