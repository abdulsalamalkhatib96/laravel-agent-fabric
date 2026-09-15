<?php

namespace Evolvex\AgentFabric\Enums;

enum TrustLevel: string
{
    case System = 'system';
    case Developer = 'developer';
    case TrustedBusiness = 'trusted_business';
    case User = 'user';
    case Untrusted = 'untrusted';
}
