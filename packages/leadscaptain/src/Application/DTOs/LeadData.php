<?php

declare(strict_types=1);

namespace Leadscaptain\Application\DTOs;

use DateTimeImmutable;
use Leadscaptain\Domain\Entities\Lead;
use Throwable;

final readonly class LeadData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $externalId,
        public ?string $email,
        public ?string $name,
        public array $metadata = [],
        public ?DateTimeImmutable $remoteCreatedAt = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $externalId = (string) (
            $data['key']
            ?? $data['leadscaptain_public_identifier']
            ?? $data['leadscaptain_id']
            ?? $data['external_id']
            ?? $data['id']
            ?? ''
        );

        if (! empty($data['emails']) && is_array($data['emails'])) {
            $email = (string) reset($data['emails']);
        } elseif (! empty($data['personal_emails']) && is_array($data['personal_emails'])) {
            $email = (string) reset($data['personal_emails']);
        } elseif (! empty($data['email']) && is_string($data['email'])) {
            $email = $data['email'];
        }

        $firstName = ! empty($data['first_name']) ? (string) $data['first_name'] : '';
        $lastName = ! empty($data['last_name']) ? (string) $data['last_name'] : '';
        $name = trim("{$firstName} {$lastName}");

        if ($name === '') {
            $name = ! empty($data['name']) ? (string) $data['name'] : (! empty($data['headline']) ? (string) $data['headline'] : null);
        }

        $remoteCreatedAt = null;
        $dateField = $data['position_started_at'] ?? $data['created_at'] ?? null;
        if (! empty($dateField)) {
            try {
                $remoteCreatedAt = new DateTimeImmutable((string) $dateField);
            } catch (Throwable) {
                $remoteCreatedAt = null;
            }
        }

        return new self(
            externalId: $externalId,
            email: $email,
            name: $name,
            metadata: $data,
            remoteCreatedAt: $remoteCreatedAt
        );
    }

    public function toDomain(): Lead
    {
        return new Lead(
            externalId: $this->externalId,
            email: $this->email,
            name: $this->name,
            metadata: $this->metadata,
            createdAt: $this->remoteCreatedAt
        );
    }
}
