<?php

namespace Evolvex\AgentFabric\Protocols\MCP;

use Evolvex\AgentFabric\Contracts\QuotaManager;
use Illuminate\Database\ConnectionInterface;

final class McpServerPolicy
{
    public function __construct(private readonly ConnectionInterface $db,private readonly QuotaManager $quotas,private readonly McpClientRegistry $clients){}
    public function assertAllowed(?string $tenantId,string $server,string $tool):void
    {
        $row=$this->db->table('ai_mcp_servers')->where('name',$server)->where(function($q)use($tenantId){$q->whereNull('tenant_id');if($tenantId!==null)$q->orWhere('tenant_id',$tenantId);})->where('enabled',true)->orderByDesc('tenant_id')->first();
        if(!$row){if(config('agent-fabric.protocols.mcp.require_registered_server',false))throw new \RuntimeException("MCP server [{$server}] is not registered.");return;}
        if((string)$row->trust_level==='blocked')throw new \RuntimeException("MCP server [{$server}] is blocked by trust policy.");
        $actual=$this->clients->fingerprint($server);if($actual!==null&&!hash_equals((string)$row->fingerprint,$actual))throw new \RuntimeException("MCP server [{$server}] fingerprint changed; re-approval is required.");
        $allowed=$row->allowed_tools?json_decode((string)$row->allowed_tools,true):[];
        if($allowed!==[]&&!in_array($tool,$allowed,true)&&!in_array('*',$allowed,true))throw new \RuntimeException("MCP tool [{$server}.{$tool}] is not allowlisted.");
        if(!$this->quotas->consume('mcp_server',($tenantId??'*').':'.$server,1,(int)$row->requests_per_minute,60))throw new \RuntimeException("MCP server [{$server}] rate limit exceeded.");
    }
}
