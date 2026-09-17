<?php

namespace Evolvex\AgentFabric\Observability;

use Illuminate\Database\ConnectionInterface;

final class QueueHealthService
{
    public function __construct(private readonly ConnectionInterface $db){}
    public function snapshot():array
    {
        return [
            'agent_runs'=>['running'=>$this->db->table('ai_runs')->where('status','running')->count(),'waiting'=>$this->db->table('ai_runs')->whereIn('status',['waiting_for_tool','waiting_for_approval','waiting_for_user'])->count(),'ambiguous'=>$this->db->table('ai_runs')->where('status','ambiguous')->count()],
            'workflows'=>['running'=>$this->db->table('ai_workflow_runs')->where('status','running')->count(),'retrying'=>$this->db->table('ai_workflow_runs')->where('status','retrying')->count(),'ambiguous'=>$this->db->table('ai_workflow_runs')->where('status','ambiguous')->count()],
            'outbox'=>['pending'=>$this->db->table('ai_outbox')->whereIn('status',['pending','failed'])->count(),'processing'=>$this->db->table('ai_outbox')->where('status','processing')->count()],
            'remote_operations'=>['ambiguous'=>$this->db->table('ai_remote_operations')->where('status','ambiguous')->count()],
            'queues'=>config('agent-fabric.queues',[]),
        ];
    }
}
