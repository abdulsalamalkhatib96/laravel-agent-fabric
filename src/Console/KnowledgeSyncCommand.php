<?php

namespace Evolvex\AgentFabric\Console;

use Evolvex\AgentFabric\Contracts\KnowledgeSource;
use Evolvex\AgentFabric\Knowledge\KnowledgeIndexer;
use Illuminate\Console\Command;

final class KnowledgeSyncCommand extends Command
{
    protected $signature='agent-fabric:knowledge:sync {source : KnowledgeSource class}'; protected $description='Synchronize a knowledge source';
    public function handle(KnowledgeIndexer $indexer): int
    {
        $class=(string)$this->argument('source'); $source=app($class); if(!$source instanceof KnowledgeSource){$this->error("{$class} must implement KnowledgeSource.");return self::FAILURE;}
        $result=$indexer->sync($source);$this->info("Indexed {$result['indexed']} document(s); skipped {$result['skipped']} unchanged; deleted {$result['deleted']} stale document(s).");return self::SUCCESS;
    }
}
