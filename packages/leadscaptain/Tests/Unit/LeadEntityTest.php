<?php

declare(strict_types=1);

namespace Leadscaptain\Tests\Unit;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Leadscaptain\Domain\Entities\Lead;

final class LeadEntityTest extends TestCase
{
    public function test_lead_entity_instantiates_with_strict_types(): void
    {
        $createdAt = new DateTimeImmutable('2026-09-11 12:00:00');
        $lead = new Lead(
            externalId: 'ext_999',
            email: 'bilal@leadscaptain.com',
            name: 'Bilal Shuja',
            metadata: ['tier' => 'enterprise', 'score' => 95],
            createdAt: $createdAt
        );

        $this->assertSame('ext_999', $lead->externalId);
        $this->assertSame('bilal@leadscaptain.com', $lead->email);
        $this->assertSame('Bilal Shuja', $lead->name);
        $this->assertSame(['tier' => 'enterprise', 'score' => 95], $lead->metadata);
        $this->assertSame($createdAt, $lead->createdAt);

        $dbArray = $lead->toDatabaseArray();
        $this->assertSame('ext_999', $dbArray['external_id']);
        $this->assertSame('2026-09-11 12:00:00', $dbArray['remote_created_at']);
    }
}