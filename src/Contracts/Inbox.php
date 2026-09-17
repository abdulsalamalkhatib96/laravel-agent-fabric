<?php

namespace Evolvex\AgentFabric\Contracts;

interface Inbox
{
    public function begin(string $consumer, string $messageId, ?string $tenantId = null): bool;
    public function complete(string $consumer, string $messageId): void;
    public function fail(string $consumer, string $messageId, string $message): void;
}
