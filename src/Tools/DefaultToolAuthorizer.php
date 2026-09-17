<?php

namespace Evolvex\AgentFabric\Tools;

use Evolvex\AgentFabric\Contracts\AgentTool;
use Evolvex\AgentFabric\Contracts\ToolAuthorizer;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\AuthorizationDecision;
use Evolvex\AgentFabric\Policies\ToolGovernanceRegistry;

final class DefaultToolAuthorizer implements ToolAuthorizer
{
    public function __construct(private readonly ToolGovernanceRegistry $policies){}
    public function authorize(AgentTool $tool,AgentContext $context,array $arguments):AuthorizationDecision
    {
        if(config('agent-fabric.security.require_tenant',true)&&(string)$context->tenantId==='')return AuthorizationDecision::deny('Tenant context is required.');
        if($reason=$this->policies->authorize($tool->name(),$context,$arguments))return AuthorizationDecision::deny($reason);
        return AuthorizationDecision::allow();
    }
}
