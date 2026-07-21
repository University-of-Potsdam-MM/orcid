<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Value;

use InvalidArgumentException;
use Stringable;

/**
 * Value Object for ORCID ID
 * Format: 0000-0000-0000-0000 (last digit can be X).
 */
class OrcidId implements Stringable
{
    private const PATTERN = '/^[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{3}[X0-9]$/';

    private function __construct(private readonly string $orcidId) {}

    public function __toString(): string
    {
        return $this->orcidId;
    }

    /**
     * Create OrcidId from a string with validation.
     *
     * @throws InvalidArgumentException if a format is invalid
     */
    public static function fromString(string $orcidId): self
    {
        $orcidId = trim($orcidId);

        if (!self::isValidFormat($orcidId)) {
            throw new InvalidArgumentException(
                sprintf('Invalid ORCID ID format: %s. Expected format: 0000-0000-0000-0000', $orcidId)
            );
        }

        return new self($orcidId);
    }

    /**
     * Check if an ORCID ID format is valid.
     */
    public static function isValidFormat(string $orcidId): bool
    {
        return (bool)preg_match(self::PATTERN, $orcidId);
    }

    public function getValue(): string
    {
        return $this->orcidId;
    }

    public function equals(self $other): bool
    {
        return $this->orcidId === $other->orcidId;
    }
}
