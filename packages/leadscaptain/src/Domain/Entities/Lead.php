<?php

declare(strict_types=1);

namespace Leadscaptain\Domain\Entities;

use DateTimeImmutable;
use Throwable;

final readonly class Lead
{
    public function __construct(
        public string $externalId,
        public ?string $email,
        public ?string $name,
        public array $metadata = [],
        public ?DateTimeImmutable $createdAt = null
    ) {}

    public static function fromApiPayload(array $payload): self
    {
        $externalId = (string) ($payload['key']
            ?? $payload['leadscaptain_public_identifier']
            ?? $payload['leadscaptain_id']
            ?? '');

        $email = null;
        if (! empty($payload['emails']) && is_array($payload['emails'])) {
            $email = (string) reset($payload['emails']);
        } elseif (! empty($payload['personal_emails']) && is_array($payload['personal_emails'])) {
            $email = (string) reset($payload['personal_emails']);
        }

        $firstName = ! empty($payload['first_name']) ? (string) $payload['first_name'] : '';
        $lastName = ! empty($payload['last_name']) ? (string) $payload['last_name'] : '';
        $name = trim("{$firstName} {$lastName}");
        $name = $name !== '' ? $name : (! empty($payload['headline']) ? (string) $payload['headline'] : null);

        $remoteCreatedAt = null;
        $dateField = $payload['position_started_at'] ?? $payload['created_at'] ?? null;
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
            metadata: $payload,
            createdAt: $remoteCreatedAt
        );
    }

    public function toDatabaseArray(): array
    {
        $d = $this->metadata;

        $firstName = ! empty($d['first_name']) ? (string) $d['first_name'] : null;
        $lastName = ! empty($d['last_name']) ? (string) $d['last_name'] : null;

        return [
            // Identifiers
            'external_id' => $this->externalId,
            'leadscaptain_public_identifier' => $d['leadscaptain_public_identifier'] ?? null,
            'leadscaptain_id' => $d['leadscaptain_id'] ?? null,
            'leadscaptain_member_id' => (int) ($d['leadscaptain_member_id'] ?? 0),

            // Person
            'email' => $this->email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $this->name,
            'gender' => $d['gender'] ?? null,
            'country_code' => $d['country_code'] ?? null,
            'birth_year' => isset($d['birth_year']) ? (string) $d['birth_year'] : null,
            'headline' => $d['headline'] ?? null,
            'summary' => $d['summary'] ?? null,

            // Professional
            'team' => $d['team'] ?? null,
            'hierarchy' => $d['hierarchy'] ?? null,
            'persona' => $d['persona'] ?? null,
            'industry_name' => $d['industry_name'] ?? null,
            'position_title' => $d['position_title'] ?? null,
            'position_location' => $d['position_location'] ?? null,
            'position_description' => $d['position_description'] ?? null,

            // Company & CRM
            'company_name' => $d['company_name'] ?? null,
            'company_leadscaptain_id' => $d['company_leadscaptain_id'] ?? null,
            'company_leadscaptain_universal_name' => $d['company_leadscaptain_universal_name'] ?? null,
            'company_salesforce_id' => $d['company_salesforce_id'] ?? null,
            'company_spendesk_id' => $d['company_spendesk_id'] ?? null,
            'company_hubspot_id' => $d['company_hubspot_id'] ?? null,

            // Social & Verification
            'linkedin_url' => $d['linkedin_url'] ?? null,
            'linkedin_source' => (bool) ($d['linkedin_source'] ?? false),
            'sales_navigator_source' => (bool) ($d['sales_navigator_source'] ?? false),
            'email_status' => $d['email_status'] ?? null,
            'email_last_checked' => isset($d['email_last_checked']) ? (string) $d['email_last_checked'] : null,

            // Metadata & Timestamps
            'metadata' => json_encode($this->metadata, JSON_THROW_ON_ERROR),
            'position_started_at' => ! empty($d['position_started_at']) ? (string) $d['position_started_at'] : null,
            'remote_created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => now()->toDateTimeString(),
        ];
    }
}
