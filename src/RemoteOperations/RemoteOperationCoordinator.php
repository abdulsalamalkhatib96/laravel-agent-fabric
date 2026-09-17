<?php

namespace Evolvex\AgentFabric\RemoteOperations;

use Evolvex\AgentFabric\Contracts\RemoteOperationReconciler;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\RemoteOperationOutcome;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use Throwable;

final class RemoteOperationCoordinator
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function execute(string $runId,string $name,string $idempotencyKey,string $reconciliationKey,AgentContext $context,callable $send,?RemoteOperationReconciler $reconciler=null,array $metadata=[]):RemoteOperationOutcome
    {
        $id=(string)Str::uuid();
        $inserted=$this->db->table('ai_remote_operations')->insertOrIgnore(['id'=>$id,'run_id'=>$runId,'tenant_id'=>(string)$context->tenantId,'operation_name'=>$name,'idempotency_key'=>$idempotencyKey,'reconciliation_key'=>$reconciliationKey,'status'=>'prepared','metadata'=>json_encode($metadata),'attempts'=>0,'created_at'=>now(),'updated_at'=>now()]);
        $row=$this->db->table('ai_remote_operations')->where('idempotency_key',$idempotencyKey)->first();
        if(!$row) return RemoteOperationOutcome::ambiguous(null,null,['remote-operation:missing-record']);
        if($row->status==='succeeded')return RemoteOperationOutcome::succeeded(json_decode((string)$row->result,true),$row->provider_reference,['remote-operation:'.$row->id]);
        if($row->status==='failed')return RemoteOperationOutcome::failed(json_decode((string)$row->result,true),$row->provider_reference,['remote-operation:'.$row->id]);
        if($inserted!==1 && in_array($row->status,['sending','ambiguous','reconciling'],true))return $this->reconcile((string)$row->id,$context,$reconciler);
        $this->db->table('ai_remote_operations')->where('id',$row->id)->update(['status'=>'sending','attempts'=>$this->db->raw('attempts + 1'),'updated_at'=>now()]);
        try{
            $outcome=$send();if(!$outcome instanceof RemoteOperationOutcome)$outcome=RemoteOperationOutcome::succeeded($outcome);
            $this->persist((string)$row->id,$outcome);return $outcome;
        }catch(Throwable $e){
            $this->db->table('ai_remote_operations')->where('id',$row->id)->update(['status'=>'ambiguous','last_error'=>$e->getMessage(),'updated_at'=>now()]);
            if($reconciler)return $this->reconcile((string)$row->id,$context,$reconciler);
            return RemoteOperationOutcome::ambiguous(null,null,['remote-operation:'.$row->id],['exception'=>$e::class,'message'=>$e->getMessage()]);
        }
    }
    public function reconcile(string $id,AgentContext $context,?RemoteOperationReconciler $reconciler):RemoteOperationOutcome
    {
        $row=$this->db->table('ai_remote_operations')->where('id',$id)->where('tenant_id',(string)$context->tenantId)->first();
        if(!$row||!$reconciler)return RemoteOperationOutcome::ambiguous(null,null,['remote-operation:'.$id]);
        $this->db->table('ai_remote_operations')->where('id',$id)->update(['status'=>'reconciling','updated_at'=>now()]);
        try{$outcome=$reconciler->reconcile((string)$row->operation_name,(string)$row->reconciliation_key,$context,json_decode((string)$row->metadata,true)?:[]);$this->persist($id,$outcome);return $outcome;}catch(Throwable $e){$this->db->table('ai_remote_operations')->where('id',$id)->update(['status'=>'ambiguous','last_error'=>$e->getMessage(),'updated_at'=>now()]);return RemoteOperationOutcome::ambiguous(null,$row->provider_reference,['remote-operation:'.$id],['exception'=>$e::class]);}
    }
    private function persist(string $id,RemoteOperationOutcome $outcome):void{$this->db->table('ai_remote_operations')->where('id',$id)->update(['status'=>$outcome->status,'result'=>json_encode($outcome->data),'provider_reference'=>$outcome->providerReference,'evidence'=>json_encode($outcome->evidence),'resolved_at'=>$outcome->final()?now():null,'updated_at'=>now()]);}
}
