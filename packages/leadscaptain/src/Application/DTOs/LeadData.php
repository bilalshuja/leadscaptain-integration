<?php

declare(strict_types=1);

namespace Leadscaptain\Application\DTOs;

use DateTimeImmutable;
use Leadscaptain\Domain\Entities\Lead;

final readonly class LeadData
{
    public function __construct(
        public string $id,
        public ?string $email,
        public ?string $name,
        public array $attributes = [],
        public ?string $createdAt = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? $data['uuid'] ?? ''),
            email: isset($data['email']) ? (string) $data['email'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            attributes: (array) ($data['metadata'] ?? $data['attributes'] ?? []),
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null
        );
    }

    public function toDomain(): Lead
    {
        $created = null;
        if ($this->createdAt !== null) {
            $created = DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, $this->createdAt)
                ?: new DateTimeImmutable($this->createdAt);
        }

        return new Lead(
            externalId: $this->id,
            email: $this->email,
            name: $this->name,
            metadata: $this->attributes,
            createdAt: $created
        );
    }
}
