<?php

namespace Evolvex\AgentFabric\Enums;

enum VerificationStatus: string
{
    case Verified = 'verified';
    case PartiallyVerified = 'partially_verified';
    case Unverified = 'unverified';
    case InsufficientData = 'insufficient_data';
    case ConflictingData = 'conflicting_data';
}
