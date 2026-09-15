<?php

namespace Evolvex\AgentFabric\Verification;

use Evolvex\AgentFabric\Contracts\Verifier;
use Evolvex\AgentFabric\Data\VerificationResult;
use Evolvex\AgentFabric\Enums\VerificationStatus;

final class GroundedVerifier implements Verifier
{
    public function verify(string $answer, array $evidence, array $context = []): VerificationResult
    {
        if (trim($answer) === '') return new VerificationResult(VerificationStatus::InsufficientData,0,['Empty answer.'],$evidence);
        $toolClaims = (bool) ($context['tool_claims'] ?? false);
        $knowledgeAvailable=(bool)($context['knowledge_available']??false);
        if ($toolClaims && config('agent-fabric.verification.require_evidence_for_tool_claims',true) && $evidence === []) return new VerificationResult(VerificationStatus::Unverified,0.2,['Action/result claim has no tool evidence.'],[]);
        if ($knowledgeAvailable && config('agent-fabric.verification.require_citations_when_knowledge_available',true) && $evidence===[]) return new VerificationResult(VerificationStatus::Unverified,0.25,['Retrieved knowledge was available but the answer supplied no valid citation.'],[]);
        $score = $evidence === [] ? .55 : min(1.0,.65 + min(count($evidence),5)*.07);
        $min = (float) config('agent-fabric.verification.minimum_grounding_score',.5);
        return new VerificationResult($score >= $min ? VerificationStatus::Verified : VerificationStatus::PartiallyVerified,$score,[],$evidence);
    }
}
