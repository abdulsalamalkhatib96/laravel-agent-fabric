<?php

namespace Evolvex\AgentFabric\Tests\Unit;

use Evolvex\AgentFabric\Data\ModelProfile;
use PHPUnit\Framework\TestCase;

final class ModelGovernanceTest extends TestCase
{
    public function test_model_profile_enforces_classification_region_and_retention(): void
    {
        $profile = new ModelProfile(
            key: 'private', provider: 'provider', model: 'model', capabilities: ['reasoning'],
            regions: ['uae'], allowedClassifications: ['public','internal','sensitive'], zeroRetention: true,
        );

        self::assertTrue($profile->permits('sensitive', 'uae', true));
        self::assertFalse($profile->permits('secret', 'uae', true));
        self::assertFalse($profile->permits('sensitive', 'eu', true));
    }
}
