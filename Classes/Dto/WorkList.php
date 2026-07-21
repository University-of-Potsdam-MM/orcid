<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Dto;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;
use UniPotsdam\Orcid\Value\OrcidIdCollection;

/**
 * Data Transfer Object for Work List.
 */
class WorkList implements IteratorAggregate, Countable
{
    /**
     * @param Work[] $works
     * @param int[] $remotePutCodes Authoritative snapshot of put-codes currently on ORCID for this iD.
     */
    public function __construct(
        public readonly OrcidIdCollection $orcidIds,
        public readonly ?Person $person,
        private array $works,
        private readonly array $remotePutCodes = [],
    ) {}

    /**
     * @return int[]
     */
    public function getRemotePutCodes(): array
    {
        return $this->remotePutCodes;
    }

    public function getWorks(): array
    {
        return $this->works;
    }

    public function getPersonDisplayName(): string
    {
        if ($this->orcidIds->count() !== 1) {
            return '';
        }

        return $this->person ? $this->person->displayName() : '';
    }

    public function getPerson(): Person
    {
        return $this->person;
    }

    public function add(Work $work): void
    {
        // Check for duplicates by put-code
        foreach ($this->works as $existing) {
            if ($existing->getPutCode() === $work->getPutCode()) {
                return;
            }
        }
        $this->works[] = $work;
    }

    /**
     * @return int[]
     */
    public function putCodes(): array
    {
        $putCodes = [];
        foreach ($this->works as $pub) {
            $putCodes[] = $pub->getPutCode();
        }

        // Deduplicate + stable order
        $putCodes = array_values(array_unique($putCodes));
        sort($putCodes);

        return $putCodes;
    }

    /**
     * @return array<int, Work[]>
     */
    public function groupByYearDesc(): array
    {
        $groups = [];
        foreach ($this->works as $idx => $work) {
            $year = $work->getWorkYear() ?? 0;
            $groups[$year][$idx] = $work;
        }
        krsort($groups, SORT_NUMERIC);

        return $groups;
    }

    /**
     * @return array<string, Work[]>
     */
    public function groupByType(): array
    {
        $groups = [];
        foreach ($this->works as $idx => $pub) {
            $groups[$pub->getWorkType()->value][$idx] = $pub;
        }
        ksort($groups);

        return $groups;
    }

    /**
     * @return array<Work>
     */
    public function toArray(): array
    {
        return $this->works;
    }

    public function isEmpty(): bool
    {
        return empty($this->works);
    }

    public function count(): int
    {
        return count($this->works);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->works);
    }
}
