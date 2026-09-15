<?php

namespace Evolvex\AgentFabric\Contracts;

interface FineTuneProvider
{
    public function createDataset(iterable $examples): string;
    public function validateDataset(string $datasetId): array;
    public function train(string $datasetId,array $options=[]): string;
    public function status(string $jobId): array;
}
