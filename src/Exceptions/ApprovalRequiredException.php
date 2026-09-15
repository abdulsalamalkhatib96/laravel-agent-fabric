<?php

namespace Evolvex\AgentFabric\Exceptions;

use RuntimeException;

final class ApprovalRequiredException extends RuntimeException
{
    public function __construct(public readonly string $approvalId, string $message) { parent::__construct($message); }
}
