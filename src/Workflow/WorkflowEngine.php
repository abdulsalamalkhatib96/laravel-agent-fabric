<?php

namespace Evolvex\AgentFabric\Workflow;

use Evolvex\AgentFabric\Contracts\Workflow;
use Evolvex\AgentFabric\Contracts\WorkflowStepHandler;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Enums\WorkflowStatus;
use Evolvex\AgentFabric\Enums\WorkflowStepType;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use Throwable;

final class WorkflowEngine
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function run(Workflow $workflow, AgentContext $context, array $input = []): WorkflowRunResult
    {
        $runId = (string) Str::uuid();
        $state = ['input' => $input, 'responses' => []];
        $this->db->table('ai_workflow_runs')->insert([
            'id' => $runId,
            'tenant_id' => (string) $context->tenantId,
            'workflow' => $workflow->name(),
            'version' => $workflow->version(),
            'status' => WorkflowStatus::Running->value,
            'state' => json_encode($state),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->execute($workflow, $context, $runId, $state);
    }

    public function resume(Workflow $workflow, AgentContext $context, string $runId, string $stepName, mixed $response): WorkflowRunResult
    {
        $run = $this->db->table('ai_workflow_runs')->where('id', $runId)->first();
        if (! $run) throw new \InvalidArgumentException("Unknown workflow run [{$runId}].");
        if ((string) $run->tenant_id !== (string) $context->tenantId) throw new \RuntimeException('Cross-tenant workflow resume denied.');
        if ((string) $run->workflow !== $workflow->name()) throw new \RuntimeException('Workflow definition does not match persisted run.');
        if ((string) $run->status !== WorkflowStatus::Waiting->value) throw new \RuntimeException('Workflow is not waiting for input.');

        $step = $this->db->table('ai_workflow_steps')->where('run_id', $runId)->where('step_name', $stepName)->first();
        if (! $step || (string) $step->status !== 'waiting') throw new \RuntimeException("Workflow step [{$stepName}] is not waiting.");

        $state = json_decode((string) $run->state, true) ?: [];
        $state['responses'][$stepName] = $response;
        $this->db->table('ai_workflow_steps')->where('id', $step->id)->update([
            'status' => 'completed',
            'output' => json_encode(['response' => $response]),
            'updated_at' => now(),
        ]);
        $this->db->table('ai_workflow_runs')->where('id', $runId)->update([
            'status' => WorkflowStatus::Running->value,
            'state' => json_encode($state),
            'updated_at' => now(),
        ]);

        return $this->execute($workflow, $context, $runId, $state);
    }

    public function cancel(string $runId, string|int $tenantId): void
    {
        $updated = $this->db->table('ai_workflow_runs')->where('id', $runId)->where('tenant_id', (string) $tenantId)->update([
            'status' => WorkflowStatus::Cancelled->value,
            'updated_at' => now(),
        ]);
        if ($updated !== 1) throw new \InvalidArgumentException('Workflow run not found for tenant.');
    }

    private function execute(Workflow $workflow, AgentContext $context, string $runId, array $state): WorkflowRunResult
    {
        $definition = $workflow->definition();
        $maxSteps = (int) config('agent-fabric.workflows_runtime.max_steps', 100);
        $iterations = 0;
        $records = [];

        try {
            while (true) {
                if (++$iterations > $maxSteps) throw new \RuntimeException('Workflow exceeded maximum execution steps.');
                $runStatus = (string) $this->db->table('ai_workflow_runs')->where('id', $runId)->value('status');
                if ($runStatus === WorkflowStatus::Cancelled->value) return new WorkflowRunResult($runId, WorkflowStatus::Cancelled, $state, $records);

                $completed = $this->db->table('ai_workflow_steps')->where('run_id', $runId)->where('status', 'completed')->pluck('step_name')->all();
                if (count($completed) >= count($definition->steps())) {
                    $this->db->table('ai_workflow_runs')->where('id', $runId)->update([
                        'status' => WorkflowStatus::Completed->value,
                        'state' => json_encode($state),
                        'completed_at' => now(),
                        'updated_at' => now(),
                    ]);
                    return new WorkflowRunResult($runId, WorkflowStatus::Completed, $state, $records);
                }

                $progress = false;
                foreach ($definition->steps() as $step) {
                    if (in_array($step->name, $completed, true)) continue;
                    if (array_diff($step->dependsOn, $completed) !== []) continue;
                    $existing = $this->db->table('ai_workflow_steps')->where('run_id', $runId)->where('step_name', $step->name)->first();
                    if ($existing && (string) $existing->status === 'waiting') {
                        return new WorkflowRunResult($runId, WorkflowStatus::Waiting, $state, $records, "Workflow waiting at [{$step->name}].");
                    }

                    if (in_array($step->type, [WorkflowStepType::Approval, WorkflowStepType::Human], true)) {
                        $this->persistStep($runId, $step, 'waiting', $step->input, null);
                        $this->db->table('ai_workflow_runs')->where('id', $runId)->update([
                            'status' => WorkflowStatus::Waiting->value,
                            'state' => json_encode($state),
                            'updated_at' => now(),
                        ]);
                        return new WorkflowRunResult($runId, WorkflowStatus::Waiting, $state, $records, "Workflow waiting at [{$step->name}].");
                    }

                    $handler = $step->handler ? app($step->handler) : null;
                    if (! $handler instanceof WorkflowStepHandler) throw new \RuntimeException("Workflow step [{$step->name}] has no valid handler.");
                    $this->persistStep($runId, $step, 'running', $step->input, null);
                    $result = $handler->handle($context, array_replace($state['input'] ?? [], $step->input), $state);
                    if (! $result->success) throw new \RuntimeException($result->message ?? "Workflow step [{$step->name}] failed.");
                    $state = array_replace_recursive($state, $result->statePatch);
                    $records[$step->name] = $result->output;
                    $this->persistStep($runId, $step, 'completed', $step->input, ['output' => $result->output, 'evidence' => $result->evidence]);
                    $progress = true;
                }

                if (! $progress) throw new \RuntimeException('Workflow dependency graph is cyclic or unsatisfied.');
            }
        } catch (Throwable $e) {
            if ((bool) config('agent-fabric.workflows_runtime.compensation', true)) $this->compensate($definition, $context, $runId, $state);
            $this->db->table('ai_workflow_runs')->where('id', $runId)->update([
                'status' => WorkflowStatus::Failed->value,
                'failure_message' => $e->getMessage(),
                'state' => json_encode($state),
                'updated_at' => now(),
            ]);
            throw $e;
        }
    }

    private function compensate(WorkflowDefinition $definition, AgentContext $context, string $runId, array $state): void
    {
        $completed = $this->db->table('ai_workflow_steps')->where('run_id', $runId)->where('status', 'completed')->pluck('step_name')->all();
        $steps = array_reverse($definition->steps());
        $this->db->table('ai_workflow_runs')->where('id', $runId)->update(['status' => WorkflowStatus::Compensating->value, 'updated_at' => now()]);
        foreach ($steps as $step) {
            if (! in_array($step->name, $completed, true) || ! $step->compensation) continue;
            $handler = app($step->compensation);
            if (! $handler instanceof WorkflowStepHandler) continue;
            try { $handler->handle($context, $step->input, $state); } catch (Throwable) { /* best-effort; reconciliation owns unresolved ambiguity */ }
        }
    }

    private function persistStep(string $runId, WorkflowStep $step, string $status, array $input, mixed $output): void
    {
        $existing = $this->db->table('ai_workflow_steps')->where('run_id', $runId)->where('step_name', $step->name)->first();
        $payload = ['status' => $status, 'input' => json_encode($input), 'output' => $output === null ? null : json_encode($output), 'updated_at' => now()];
        if ($existing) { $this->db->table('ai_workflow_steps')->where('id', $existing->id)->update($payload); return; }
        $this->db->table('ai_workflow_steps')->insert(array_merge($payload, [
            'id' => (string) Str::uuid(), 'run_id' => $runId, 'step_name' => $step->name,
            'step_type' => $step->type->value, 'created_at' => now(),
        ]));
    }
}
