<?php

namespace Evolvex\AgentFabric\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

final class MakeToolCommand extends Command
{
    protected $signature='make:agent-tool {name}'; protected $description='Create an Agent Fabric tool';
    public function handle(Filesystem $files): int
    {
        $class=(string)$this->argument('name');$path=app_path('Ai/Tools/'.$class.'.php');if($files->exists($path)){$this->error('Tool already exists.');return self::FAILURE;}$files->ensureDirectoryExists(dirname($path));
        $stub=str_replace('{{ class }}',$class,<<<'PHP'
<?php

namespace App\Ai\Tools;

use Evolvex\AgentFabric\Contracts\AgentTool;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\ToolResult;
use Evolvex\AgentFabric\Enums\ToolRisk;

final class {{ class }} implements AgentTool
{
    public function name(): string { return '{{ class }}'; }
    public function description(): string { return 'Describe this tool.'; }
    public function risk(): ToolRisk { return ToolRisk::Read; }
    public function inputSchema(): array { return ['type'=>'object','properties'=>[],'required'=>[]]; }
    public function execute(AgentContext $context, array $arguments): ToolResult { return ToolResult::success([]); }
}
PHP);
        $files->put($path,$stub);$this->info("Created {$path}");return self::SUCCESS;
    }
}
