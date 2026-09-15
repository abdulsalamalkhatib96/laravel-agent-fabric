<?php

namespace Evolvex\AgentFabric\Models;

use Evolvex\AgentFabric\Data\ModelProfile;

final class ConfigModelCatalog
{
    /** @return list<ModelProfile> */
    public function all(): array
    {
        $profiles=[];
        foreach(config('agent-fabric.routing.models',[]) as $key=>$config){
            $profiles[]=new ModelProfile(
                key:(string)$key,provider:(string)($config['provider']??''),model:(string)($config['model']??''),capabilities:$config['capabilities']??[],
                quality:(float)($config['quality']??.5),toolAccuracy:(float)($config['tool_accuracy']??.5),reliability:(float)($config['reliability']??.5),
                latencyScore:(float)($config['latency']??.5),costScore:(float)($config['cost']??.5),historicalEval:(float)($config['historical_eval']??.5),
                contextWindow:isset($config['context_window'])?(int)$config['context_window']:null,metadata:$config['metadata']??[],regions:$config['regions']??[],
                allowedClassifications:$config['allowed_classifications']??['public','internal','confidential','sensitive'],privacyTier:(string)($config['privacy_tier']??'standard'),
                zeroRetention:(bool)($config['zero_retention']??false),dataResidency:$config['data_residency']??null,
            );
        }
        return $profiles;
    }
}
