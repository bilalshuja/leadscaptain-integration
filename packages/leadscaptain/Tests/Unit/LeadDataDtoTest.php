<?php

declare(strict_types=1);

namespace Leadscaptain\Tests\Unit;

use Leadscaptain\Application\DTOs\LeadData;
use PHPUnit\Framework\TestCase;

final class LeadDataDtoTest extends TestCase
{
    public function test_from_array_maps_correctly_to_domain_lead(): void
    {
        $payload = [
            'id' => 'lead_uuid_456',
            'email' => 'client@leadscaptain.com',
            'name' => 'Stefan',
            'metadata' => ['source' => 'api_v1', 'verified' => true],
            'created_at' => '2026-09-11T12:00:00+00:00',
        ];

        $dto = LeadData::fromArray($payload);
        $domainEntity = $dto->toDomain();

        $this->assertSame('lead_uuid_456', $domainEntity->externalId);
        $this->assertSame('client@leadscaptain.com', $domainEntity->email);
        $this->assertSame('Stefan', $domainEntity->name);
        $this->assertTrue($domainEntity->metadata['verified']);
        $this->assertSame('2026-09-11 12:00:00', $domainEntity->createdAt?->format('Y-m-d H:i:s'));
    }
}
