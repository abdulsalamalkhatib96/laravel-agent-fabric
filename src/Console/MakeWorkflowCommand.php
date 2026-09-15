<?php

namespace Evolvex\AgentFabric\Console;

use Illuminate\Console\GeneratorCommand;

final class MakeWorkflowCommand extends GeneratorCommand
{
    protected $name='make:agent-workflow';
    protected $description='Create an Agent Fabric workflow.';
    protected $type='Workflow';
    protected function getStub(): string{return __DIR__.'/stubs/workflow.stub';}
    protected function getDefaultNamespace($rootNamespace): string{return $rootNamespace.'\Ai\Workflows';}
}
