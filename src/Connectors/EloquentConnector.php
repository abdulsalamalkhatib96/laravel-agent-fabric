<?php

namespace Evolvex\AgentFabric\Connectors;

use Evolvex\AgentFabric\Contracts\Connector;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\ConnectorAction;
use Evolvex\AgentFabric\Data\ConnectorQuery;
use Evolvex\AgentFabric\Data\ConnectorResult;
use Evolvex\AgentFabric\Data\ConnectorSchema;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

final class EloquentConnector implements Connector
{
    /** @param array<string, array{model:class-string<Model>, fields:list<string>, tenant_column?:string}> $resources */
    public function __construct(private readonly string $connectorName, private readonly array $resources) {}

    public function name(): string { return $this->connectorName; }
    public function schema(): ConnectorSchema { return new ConnectorSchema($this->name(), array_keys($this->resources), [], ['query']); }

    public function query(AgentContext $context, ConnectorQuery $query): ConnectorResult
    {
        $config=$this->resources[$query->resource]??null;
        if(!$config) return ConnectorResult::failure('Unknown resource.');
        $model=$config['model']; $allowed=$config['fields']??[];
        $fields=$query->fields===[]?$allowed:array_values(array_intersect($query->fields,$allowed));
        if($fields===[]) throw new RuntimeException('No allowed fields requested.');
        $builder=$model::query();
        if(($tenant=$config['tenant_column']??null)!==null) $builder->where($tenant,$context->tenantId);
        foreach($query->filters as $field=>$value){ if(!in_array($field,$allowed,true)) throw new RuntimeException("Filter field [{$field}] is not allowed."); $builder->where($field,$value); }
        foreach($query->sort as $field=>$direction){ if(in_array($field,$allowed,true)) $builder->orderBy($field,strtolower((string)$direction)==='desc'?'desc':'asc'); }
        return ConnectorResult::success($builder->limit(max(1,min($query->limit,200)))->get($fields)->toArray());
    }

    public function execute(AgentContext $context, ConnectorAction $action): ConnectorResult
    { return ConnectorResult::failure('EloquentConnector is query-only by design. Register explicit tools for writes.'); }
}
