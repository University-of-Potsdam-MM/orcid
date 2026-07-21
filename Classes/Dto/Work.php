<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Dto;

use JsonException;
use UniPotsdam\Orcid\Value\OrcidId;

/**
 * Data Transfer Object for Work
 * Represents a single work from ORCID.
 */
readonly class Work
{
    /**
     * @param Person[] $contributors
     */
    public function __construct(
        private OrcidId $orcidId,
        private int $putCode,
        private string $title,
        private WorkType $type,
        private ?int $year,
        private ?int $month,
        private ?string $journalTitle,
        private ?string $doi,
        private ?string $workUrl,
        private array $contributors = [],
        private int $lastModified = 0,
        private array $raw = [],
        private ?string $citationType = null,
        private ?string $citationValue = null,
    ) {}

    public function doiUrl(): ?string
    {
        return $this->doi ? 'https://doi.org/' . $this->doi : null;
    }

    public function getPutCode(): int
    {
        return $this->putCode;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getWorkYear(): ?int
    {
        return $this->year;
    }

    public function getWorkMonth(): ?int
    {
        return $this->month;
    }

    public function getWorkType(): WorkType
    {
        return $this->type;
    }

    public function getLastModified(): int
    {
        return $this->lastModified;
    }

    public function getContributors(): array
    {
        return $this->contributors;
    }

    public function getOrcidId(): OrcidId
    {
        return $this->orcidId;
    }

    public function getJournalTitle(): ?string
    {
        return $this->journalTitle;
    }

    public function getWorkUrl(): ?string
    {
        return $this->workUrl;
    }

    public function getDoi(): ?string
    {
        return $this->doi;
    }

    public function getCitationType(): ?string
    {
        return $this->citationType;
    }

    public function getCitationValue(): ?string
    {
        return $this->citationValue;
    }

    /**
     * @throws JsonException
     */
    public function getRawDataAsJson(): string
    {
        return json_encode($this->raw, JSON_THROW_ON_ERROR);
    }

    public function getRawData(): array
    {
        return $this->raw;
    }
}
