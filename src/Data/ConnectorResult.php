<?php

namespace Evolvex\AgentFabric\Data;

final readonly class ConnectorResult
{
    public function __construct(
        public bool $success,
        public mixed $data = null,
        public ?string $message = null,
        public ?string $nextCursor = null,
        public array $evidence = [],
        public array $metadata = [],
    ) {}

    public static function success(mixed $data = null, ?string $nextCursor = null, array $evidence = []): self
    { return new self(true, $data, null, $nextCursor, $evidence); }

    public static function failure(string $message): self
    { return new self(false, null, $message); }
}
