<?php

namespace Evolvex\AgentFabric\Identity;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final class DelegationService
{
    public function __construct(private readonly ConnectionInterface $db) {}
    public function issue(string $tenantId,string $fromAgent,string $toAgent,array $scopes,int $ttlSeconds=300):string
    {
        $plain=Str::random(64);$hash=hash('sha256',$plain);
        $this->db->table('ai_delegations')->insert(['id'=>(string)Str::uuid(),'tenant_id'=>$tenantId,'from_agent'=>$fromAgent,'to_agent'=>$toAgent,'token_hash'=>$hash,'scopes'=>json_encode(array_values($scopes)),'expires_at'=>now()->addSeconds($ttlSeconds),'created_at'=>now(),'updated_at'=>now()]);
        return $plain;
    }
    public function validate(string $token,string $tenantId,string $toAgent,string $scope):bool
    {
        $row=$this->db->table('ai_delegations')->where('token_hash',hash('sha256',$token))->where('tenant_id',$tenantId)->where('to_agent',$toAgent)->whereNull('revoked_at')->where('expires_at','>',now())->first();
        if(!$row)return false;$scopes=json_decode((string)$row->scopes,true)?:[];return in_array($scope,$scopes,true)||in_array('*',$scopes,true);
    }
    public function revoke(string $token):void{$this->db->table('ai_delegations')->where('token_hash',hash('sha256',$token))->update(['revoked_at'=>now(),'updated_at'=>now()]);}
}
