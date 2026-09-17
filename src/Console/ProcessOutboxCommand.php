<?php

namespace Evolvex\AgentFabric\Console;

use Evolvex\AgentFabric\Contracts\Outbox;
use Illuminate\Console\Command;

final class ProcessOutboxCommand extends Command
{
    protected $signature='agent-fabric:outbox {--limit=100}';
    protected $description='Claim pending Agent Fabric outbox messages and display them for a configured dispatcher';
    public function handle(Outbox $outbox):int
    {
        $worker=gethostname().':'.getmypid();$messages=$outbox->claim($worker,max(1,(int)$this->option('limit')),(int)config('agent-fabric.reliability.outbox.lease_seconds',60));
        foreach($messages as $message){$this->line(json_encode(['id'=>$message['id'],'topic'=>$message['topic'],'payload'=>json_decode((string)$message['payload'],true)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));}
        $this->comment('Messages remain leased until your application dispatcher acknowledges or fails them.');return self::SUCCESS;
    }
}
