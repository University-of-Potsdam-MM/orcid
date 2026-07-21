<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Value;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Collection of OrcidIDs.
 */
class OrcidIdCollection implements IteratorAggregate, Countable
{
    /**
     * @var array<OrcidId>
     */
    private array $orcidIds = [];

    private function __construct() {}

    public static function fromOrcidIds(OrcidId ...$orcidIds): self
    {
        $collection = new self();
        $collection->orcidIds = $orcidIds;

        return $collection;
    }

    public static function fromStrings(string ...$orcidIdStrings): self
    {
        $collection = new self();
        foreach ($orcidIdStrings as $orcidIdString) {
            $collection->orcidIds[] = OrcidId::fromString($orcidIdString);
        }

        return $collection;
    }

    public static function fromCommaSeparatedList(string $orcidIds): self
    {
        $parts = GeneralUtility::trimExplode(',', $orcidIds, true);

        return self::fromStrings(...$parts);
    }

    public function add(OrcidId $orcidId): void
    {
        // Check for duplicates
        foreach ($this->orcidIds as $existingId) {
            if ($existingId->equals($orcidId)) {
                return;
            }
        }
        $this->orcidIds[] = $orcidId;
    }

    public function contains(OrcidId $orcidId): bool
    {
        foreach ($this->orcidIds as $existingId) {
            if ($existingId->equals($orcidId)) {
                return true;
            }
        }

        return false;
    }

    public function getFirst(): ?OrcidId
    {
        return $this->orcidIds[0] ?? null;
    }

    public function isEmpty(): bool
    {
        return empty($this->orcidIds);
    }

    public function count(): int
    {
        return count($this->orcidIds);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->orcidIds);
    }

    /**
     * Get as an array of strings.
     *
     * @return array<string>
     */
    public function toStringArray(): array
    {
        return array_map(static fn(OrcidId $id) => $id->getValue(), $this->orcidIds);
    }
}
