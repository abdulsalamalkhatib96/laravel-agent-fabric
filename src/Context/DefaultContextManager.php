<?php

namespace Evolvex\AgentFabric\Context;

use Evolvex\AgentFabric\Contracts\ContextManager;
use Evolvex\AgentFabric\Data\AgentContext;

final class DefaultContextManager implements ContextManager
{
    public function compact(string $system, string $input, array $knowledge, array $memory, array $transcript, AgentContext $context, ?int $contextWindow = null): array
    {
        $window=$contextWindow ?: (int)config('agent-fabric.context.default_window',32000);
        $reserve=(int)config('agent-fabric.context.reserve_output_tokens',4000);
        $budget=max(1000,$window-$reserve);
        $estimate=fn(string $s):int => max(1,(int)ceil(mb_strlen($s,'UTF-8')/4));
        $base=$estimate($system)+$estimate($input);
        $remaining=max(0,$budget-$base);

        usort($knowledge,function($a,$b){return ($b->score??0)<=>($a->score??0);});
        $keptKnowledge=[];
        foreach($knowledge as $item){$cost=$estimate((string)$item->content); if($cost>$remaining)continue; $keptKnowledge[]=$item;$remaining-=$cost;}

        $keptMemory=[];
        foreach($memory as $item){$cost=$estimate(json_encode($item,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));if($cost>$remaining)continue;$keptMemory[]=$item;$remaining-=$cost;}

        $keptTranscript=[];
        foreach(array_reverse($transcript) as $item){$cost=$estimate(json_encode($item,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));if($cost>$remaining)break;array_unshift($keptTranscript,$item);$remaining-=$cost;}

        return ['knowledge'=>$keptKnowledge,'memory'=>$keptMemory,'transcript'=>$keptTranscript,'estimated_tokens'=>$budget-$remaining,'budget_tokens'=>$budget];
    }
}
