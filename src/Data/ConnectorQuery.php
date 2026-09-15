<?php

namespace Evolvex\AgentFabric\Data;

final readonly class ConnectorQuery
{
    public function __construct(
        public string $resource,
        public array $filters = [],
        public array $fields = [],
        public int $limit = 50,
        public ?string $cursor = null,
        public array $sort = [],
    ) {}
}
