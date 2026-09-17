<?php

namespace Evolvex\AgentFabric\Data;

final readonly class RemoteOperationOutcome
{
    public function __construct(
        public string $status,
        public mixed $data = null,
        public ?string $providerReference = null,
        public array $evidence = [],
        public array $metadata = [],
    ) {}

    public static function succeeded(mixed $data = null, ?string $reference = null, array $evidence = []): self { return new self('succeeded', $data, $reference, $evidence); }
    public static function failed(mixed $data = null, ?string $reference = null, array $evidence = []): self { return new self('failed', $data, $reference, $evidence); }
    public static function ambiguous(mixed $data = null, ?string $reference = null, array $evidence = []): self { return new self('ambiguous', $data, $reference, $evidence); }
    public function final(): bool { return in_array($this->status, ['succeeded', 'failed'], true); }
}
