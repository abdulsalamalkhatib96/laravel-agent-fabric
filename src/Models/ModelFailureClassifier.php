<?php

namespace Evolvex\AgentFabric\Models;

use Throwable;

final class ModelFailureClassifier
{
    public function classify(Throwable $e): string
    {
        $message=mb_strtolower($e->getMessage(),'UTF-8');
        if(str_contains($message,'rate limit')||str_contains($message,'429'))return 'rate_limit';
        if(str_contains($message,'timeout')||str_contains($message,'timed out'))return 'timeout';
        if(str_contains($message,'context')&&(str_contains($message,'length')||str_contains($message,'window')||str_contains($message,'token')))return 'context_too_large';
        if(str_contains($message,'refus')||str_contains($message,'safety'))return 'safety_refusal';
        if(str_contains($message,'connection')||str_contains($message,'network')||str_contains($message,'transport'))return 'transport';
        if(preg_match('/\b5\d\d\b/',$message))return 'provider_server_error';
        return 'unknown';
    }

    public function retryable(string $class): bool
    {
        return in_array($class,['rate_limit','timeout','transport','provider_server_error','context_too_large'],true);
    }
}
