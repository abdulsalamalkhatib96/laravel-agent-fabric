<?php

namespace Evolvex\AgentFabric\RemoteOperations;

use Evolvex\AgentFabric\Contracts\RemoteOperationReconciler;

final class ReconcilerRegistry
{
    private array $items=[];
    public function register(string $operation,string|RemoteOperationReconciler $reconciler):self{$this->items[$operation]=$reconciler;return $this;}
    public function get(string $operation):?RemoteOperationReconciler{$r=$this->items[$operation]??null;if($r===null)return null;$r=is_string($r)?app($r):$r;return $r instanceof RemoteOperationReconciler?$r:null;}
}
