<?php

namespace Evolvex\AgentFabric\Knowledge;

use Evolvex\AgentFabric\Contracts\KnowledgeAccessPolicy;
use Evolvex\AgentFabric\Data\AgentContext;

final class DefaultKnowledgeAccessPolicy implements KnowledgeAccessPolicy
{
    public function allows(AgentContext $context,array $metadata):bool
    {
        $acl=$metadata['_acl']??[];if(!is_array($acl)||$acl===[])return true;
        if(isset($acl['actors'])&&$acl['actors']!==[]&&!in_array((string)$context->actorId,array_map('strval',$acl['actors']),true))return false;
        $role=$context->metadata['role']??null;if(isset($acl['roles'])&&$acl['roles']!==[]&&!in_array((string)$role,array_map('strval',$acl['roles']),true))return false;
        $department=$context->metadata['department']??null;if(isset($acl['departments'])&&$acl['departments']!==[]&&!in_array((string)$department,array_map('strval',$acl['departments']),true))return false;
        $clearance=$context->metadata['clearance']??null;if(isset($acl['clearances'])&&$acl['clearances']!==[]&&!in_array((string)$clearance,array_map('strval',$acl['clearances']),true))return false;
        return true;
    }
}
