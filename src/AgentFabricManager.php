<?php

namespace Evolvex\AgentFabric;

use Evolvex\AgentFabric\Agents\AgentRegistry;
use Evolvex\AgentFabric\Channels\ChannelRegistry;
use Evolvex\AgentFabric\Connectors\ConnectorRegistry;
use Evolvex\AgentFabric\Contracts\ApprovalManager;
use Evolvex\AgentFabric\Contracts\RunRepository;
use Evolvex\AgentFabric\Data\ConversationEnvelope;
use Evolvex\AgentFabric\Data\AgentResult;
use Evolvex\AgentFabric\Enums\DeploymentMode;
use Evolvex\AgentFabric\Deployment\ReleaseGateResult;
use Evolvex\AgentFabric\Deployment\ReplayService;
use Evolvex\AgentFabric\Deployment\DatabaseDeploymentManager;
use Evolvex\AgentFabric\Enums\RunStatus;
use Evolvex\AgentFabric\Feedback\FeedbackRecorder;
use Evolvex\AgentFabric\Plugins\PluginRegistry;
use Evolvex\AgentFabric\Runtime\AgentRuntime;
use Evolvex\AgentFabric\Workflow\WorkflowEngine;
use Evolvex\AgentFabric\Workflow\WorkflowRegistry;

final class AgentFabricManager
{
    public function __construct(
        private readonly AgentRegistry $agents,private readonly AgentRuntime $runtime,private readonly ApprovalManager $approvals,
        private readonly RunRepository $runs,private readonly FeedbackRecorder $feedback,private readonly ConnectorRegistry $connectors,
        private readonly ChannelRegistry $channels,private readonly WorkflowRegistry $workflows,private readonly WorkflowEngine $workflowEngine,private readonly PluginRegistry $plugins,
        private readonly DatabaseDeploymentManager $deployments,private readonly ReplayService $replays,
    ){}
    public function agent(string $name): PendingAgentRun{return new PendingAgentRun($this->agents->get($name),$this->runtime);}
    public function registry(): AgentRegistry{return $this->agents;}
    public function connectors(): ConnectorRegistry{return $this->connectors;}
    public function channels(): ChannelRegistry{return $this->channels;}
    public function workflows(): WorkflowRegistry{return $this->workflows;}
    public function plugins(): PluginRegistry{return $this->plugins;}

    public function deployments(): DatabaseDeploymentManager{return $this->deployments;}
    public function replay(string $runId,?string $agent=null): AgentResult{return $this->replays->replay($runId,$agent);}
    public function deploy(string $agent,string $version,DeploymentMode $mode=DeploymentMode::Active,int $trafficPercent=100,?ReleaseGateResult $gate=null): string{return $this->deployments->deploy($agent,$version,$mode,$trafficPercent,$gate);}
    public function workflow(string $name,\Evolvex\AgentFabric\Data\AgentContext $context,array $input=[]): \Evolvex\AgentFabric\Workflow\WorkflowRunResult{return $this->workflowEngine->run($this->workflows->get($name),$context,$input);}
    public function fromEnvelope(string $agent,ConversationEnvelope $envelope): \Evolvex\AgentFabric\Data\AgentResult{return $this->agent($agent)->tenant($envelope->tenantId)->actor($envelope->actorId,$envelope->actorType)->metadata(array_replace($envelope->metadata,['channel'=>$envelope->channel->value,'modalities'=>array_map(fn($p)=>$p->modality->value,$envelope->parts)]))->ask($envelope->text());}
    public function approve(string $approvalId,string|int|null $decidedBy=null,?string $reason=null):void{$this->approvals->decide($approvalId,true,$decidedBy,$reason);}
    public function reject(string $approvalId,string|int|null $decidedBy=null,?string $reason=null):void{$this->approvals->decide($approvalId,false,$decidedBy,$reason);}
    public function cancel(string $runId):void{$this->runs->transition($runId,RunStatus::Cancelled);}
    public function feedback(string $runId,string|int $tenantId,?int $rating=null,?string $label=null,?string $reason=null,?string $correctedAnswer=null):string{return $this->feedback->record($runId,$tenantId,$rating,$label,$reason,$correctedAnswer);}
}
