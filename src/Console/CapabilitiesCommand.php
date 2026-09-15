<?php

namespace Evolvex\AgentFabric\Console;

use Evolvex\AgentFabric\Capabilities\CapabilityMatrix;
use Illuminate\Console\Command;

final class CapabilitiesCommand extends Command
{
    protected $signature='agent-fabric:capabilities';
    protected $description='Show configured model capabilities.';
    public function handle(CapabilityMatrix $matrix): int
    {
        $rows=[];foreach($matrix->available() as $capability=>$models)$rows[]=[$capability,implode(', ',$models)];
        $this->table(['Capability','Models'],$rows);return self::SUCCESS;
    }
}
