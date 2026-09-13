<?php

declare(strict_types=1);

namespace Leadscaptain\Tests\Unit;

use Leadscaptain\Application\DTOs\LeadData;
use Tests\TestCase;

final class LeadDataDtoTest extends TestCase
{
    public function test_from_array_maps_correctly_to_domain_lead(): void
    {
        $payload = [
            'key' => 'lead_uuid_456',
            'first_name' => 'Stefan',
            'last_name' => '',
            'emails' => ['client@leadscaptain.com'],
            'company_name' => 'LeadCaptain',
            'position_title' => 'Founder',
            'verified' => true,
            'position_started_at' => '2026-09-11 12:00:00',
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
