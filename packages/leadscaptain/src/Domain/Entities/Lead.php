<?php

declare(strict_types=1);

namespace Leadscaptain\Domain\Entities;

use DateTimeImmutable;

final readonly class Lead
{
    public function __construct(
        public string $externalId,
        public ?string $email,
        public ?string $name,
        public array $metadata = [],
        public ?DateTimeImmutable $createdAt = null
    ) {}

    public function toDatabaseArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'email' => $this->email,
            'name' => $this->name,
            'metadata' => json_encode($this->metadata),
            'remote_created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => now()->toDateTimeString(),
        ];
    }
}
