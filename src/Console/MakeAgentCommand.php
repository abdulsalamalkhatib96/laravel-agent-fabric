<?php

namespace Evolvex\AgentFabric\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

final class MakeAgentCommand extends Command
{
    protected $signature='make:agent-fabric {name}'; protected $description='Create an Agent Fabric agent blueprint';
    public function handle(Filesystem $files): int
    {
        $name=(string)$this->argument('name');$class=str_ends_with($name,'Agent')?$name:$name.'Agent';$path=app_path('Ai/Agents/'.$class.'.php');
        if($files->exists($path)){$this->error('Agent already exists.');return self::FAILURE;}$files->ensureDirectoryExists(dirname($path));
        $stub=str_replace('{{ class }}',$class,<<<'PHP'
<?php

namespace App\Ai\Agents;

use Evolvex\AgentFabric\Agents\AgentBlueprint;

final class {{ class }} extends AgentBlueprint
{
    public function name(): string { return '{{ class }}'; }
    public function goal(): string { return 'Describe the business goal.'; }
    public function instructions(): string { return 'Use tools and evidence. Never invent completed actions.'; }
    public function tools(): array { return []; }
    public function knowledgeSources(): array { return []; }
}
PHP);
        $files->put($path,$stub);$this->info("Created {$path}");return self::SUCCESS;
    }
}
