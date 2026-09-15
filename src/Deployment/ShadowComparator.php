<?php

namespace Evolvex\AgentFabric\Deployment;

use Evolvex\AgentFabric\Data\AgentResult;

final class ShadowComparator
{
    public function compare(AgentResult $primary,AgentResult $candidate): array
    {
        $primaryTools=$primary->metadata['tools']??[];$candidateTools=$candidate->metadata['tools']??[];
        return [
            'status_match'=>$primary->status===$candidate->status,
            'answer_match'=>trim((string)$primary->answer)===trim((string)$candidate->answer),
            'tool_trajectory_match'=>$primaryTools===$candidateTools,
            'primary_status'=>$primary->status->value,'candidate_status'=>$candidate->status->value,
        ];
    }
}
