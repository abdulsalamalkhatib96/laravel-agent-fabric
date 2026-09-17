<?php

use Evolvex\AgentFabric\Contracts\Inbox;
use Evolvex\AgentFabric\Contracts\Outbox;

$outbox = app(Outbox::class);
$inbox = app(Inbox::class);

// Add this inside the same DB transaction as the state change.
$outbox->add('order.refunded', ['order_id' => 551], 'order:551:refund:v1', 'tenant-1');

// Consumers can safely reject duplicate delivery.
if ($inbox->accept('accounting', 'message-id-from-broker')) {
    // Apply the accounting side effect exactly once from this consumer's view.
}
