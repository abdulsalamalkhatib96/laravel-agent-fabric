<?php

namespace Evolvex\AgentFabric\Capabilities;

use Evolvex\AgentFabric\Models\ConfigModelCatalog;

final class CapabilityMatrix
{
    public function __construct(private readonly ConfigModelCatalog $models){}
    public function available(): array
    {
        $out=[];foreach($this->models->all() as $model)foreach($model->capabilities as $capability)$out[(string)($capability instanceof \BackedEnum?$capability->value:$capability)][]=$model->key;
        foreach($out as &$models){$models=array_values(array_unique($models));sort($models);}ksort($out);return $out;
    }
}
