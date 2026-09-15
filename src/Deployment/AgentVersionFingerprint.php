<?php

namespace Evolvex\AgentFabric\Deployment;

use Evolvex\AgentFabric\Data\AgentDefinition;

final class AgentVersionFingerprint
{
    public function make(AgentDefinition $definition,array $components=[]): string{
        $payload=['agent'=>$definition->name,'version'=>$definition->version,'goal'=>$definition->goal,'instructions'=>$definition->instructions,'tools'=>$definition->tools,'knowledge'=>$definition->knowledgeSources,'policies'=>$definition->policies,'capabilities'=>$definition->requiredCapabilities,'workflows'=>$definition->workflows,'components'=>$components];
        return hash('sha256',json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
    }
}
