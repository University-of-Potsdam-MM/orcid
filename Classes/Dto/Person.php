<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Dto;

/**
 * Data Transfer Object for Person.
 */
final readonly class Person
{
    public function __construct(
        private string $givenName,
        private string $familyName = ''
    ) {}

    public function displayName(): string
    {
        return trim(($this->givenName ?? '') . ' ' . ($this->familyName ?? ''));
    }

    public function getGivenName(): string
    {
        return $this->givenName;
    }

    public function getFamilyName(): string
    {
        return $this->familyName;
    }

    public function isEmpty(): bool
    {
        return empty($this->givenName) && empty($this->familyName);
    }
}
