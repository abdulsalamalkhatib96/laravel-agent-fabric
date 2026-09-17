<?php

namespace Evolvex\AgentFabric\Messaging;

use Evolvex\AgentFabric\Contracts\Inbox;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final class DatabaseInbox implements Inbox
{
    public function __construct(private readonly ConnectionInterface $db) {}
    public function begin(string $consumer, string $messageId, ?string $tenantId = null): bool
    {
        return $this->db->table('ai_inbox')->insertOrIgnore(['id'=>(string)Str::uuid(),'tenant_id'=>$tenantId,'consumer'=>$consumer,'message_id'=>$messageId,'status'=>'processing','created_at'=>now(),'updated_at'=>now()]) === 1;
    }
    public function complete(string $consumer, string $messageId): void { $this->db->table('ai_inbox')->where(compact('consumer'))->where('message_id',$messageId)->update(['status'=>'completed','processed_at'=>now(),'updated_at'=>now()]); }
    public function fail(string $consumer, string $messageId, string $message): void { $this->db->table('ai_inbox')->where(compact('consumer'))->where('message_id',$messageId)->update(['status'=>'failed','last_error'=>$message,'updated_at'=>now()]); }
}
