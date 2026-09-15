<?php

namespace Evolvex\AgentFabric\Data;

final readonly class ToolResult
{
    public function __construct(
        public bool $success,
        public mixed $data = null,
        public ?string $message = null,
        public bool $ambiguous = false,
        public array $evidence = [],
        public array $metadata = [],
    ) {}

    public static function success(mixed $data = null, ?string $message = null, array $evidence = []): self
    {
        return new self(true, $data, $message, false, $evidence);
    }

    public static function failure(string $message, mixed $data = null): self
    {
        return new self(false, $data, $message);
    }

    public static function ambiguous(string $message, mixed $data = null): self
    {
        return new self(false, $data, $message, true);
    }
}
