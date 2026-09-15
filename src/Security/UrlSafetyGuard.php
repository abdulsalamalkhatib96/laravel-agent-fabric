<?php

namespace Evolvex\AgentFabric\Security;

use InvalidArgumentException;

final class UrlSafetyGuard
{
    public function assertSafe(string $url): void
    {
        $p=parse_url($url);if(!$p||!in_array(strtolower($p['scheme']??''),['http','https'],true))throw new InvalidArgumentException('Only HTTP/HTTPS URLs are allowed.');
        $host=$p['host']??'';if($host===''||strtolower($host)==='localhost')throw new InvalidArgumentException('Local URLs are forbidden.');
        $ip=filter_var($host,FILTER_VALIDATE_IP)?$host:gethostbyname($host);
        if(filter_var($ip,FILTER_VALIDATE_IP)&&!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))throw new InvalidArgumentException('Private or reserved network destinations are forbidden.');
    }
}
