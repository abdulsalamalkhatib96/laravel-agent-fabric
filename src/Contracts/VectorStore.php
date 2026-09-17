<?php

namespace Evolvex\AgentFabric\Contracts;

interface VectorStore
{
    public function upsert(string $namespace,array $records):void;
    public function query(string $namespace,array $vector,int $limit=10,array $filter=[]):array;
    public function delete(string $namespace,array $ids):void;
}
