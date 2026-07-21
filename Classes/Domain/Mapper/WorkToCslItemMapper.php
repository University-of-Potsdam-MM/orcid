<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Domain\Mapper;

use UniPotsdam\Orcid\Dto\Person;
use UniPotsdam\Orcid\Dto\Work;
use UniPotsdam\Orcid\Dto\WorkType;

final class WorkToCslItemMapper
{
    /**
     * @return array<string,mixed> CSL-JSON item
     */
    public function map(Work $work): array
    {
        $item = [
            'id' => (string)$work->getPutCode(),
            'type' => $this->mapTypeToCsl($work->getWorkType()),
            'title' => $work->getTitle(),
        ];

        $year = $work->getWorkYear();
        if ($year > 0) {
            $dateParts = [$year];

            if ($work->getWorkMonth() > 0) {
                $dateParts[] = $work->getWorkMonth();
            }

            $item['issued'] = ['date-parts' => [$dateParts]];
        }

        if ($work->getJournalTitle()) {
            $item['container-title'] = $work->getJournalTitle();
        }

        if ($work->getDoi()) {
            $item['DOI'] = $work->getDoi();
        }

        $url = $work->getWorkUrl() ?: $work->doiUrl();
        if ($url) {
            $item['URL'] = $url;
        }

        $authors = $this->mapContributorsToCslAuthors($work->getContributors());
        if ($authors !== []) {
            $item['author'] = $authors;
        }

        $item['__orcidCitationType'] = $work->getCitationType();
        $item['__orcidCitationValue'] = $work->getCitationValue();

        return $item;
    }

    private function mapTypeToCsl(WorkType $type): string
    {
        // ORCID work types -> CSL types
        return match ($type) {
            WorkType::JournalArticle => 'article-journal',
            WorkType::ConferencePaper, WorkType::ConferenceOutput, WorkType::ConferenceProceedings => 'paper-conference',
            WorkType::ConferencePresentation => 'speech',
            WorkType::Book => 'book',
            WorkType::BookChapter => 'chapter',
            WorkType::Dissertation => 'thesis',
            WorkType::Preprint => 'article',
            WorkType::Report, WorkType::WorkingPaper => 'report',
            default => 'document', // includes Other
        };
    }

    /** @param Person[] $contributors
     * @return array<int,array<string,string>>
     */
    private function mapContributorsToCslAuthors(array $contributors): array
    {
        $result = [];
        foreach ($contributors as $contributor) {
            $author = [];
            $author['family'] = $contributor->getFamilyName() ?? '';

            $author['given'] = $contributor->getGivenName() ?? '';

            $result[] = $author;
        }

        return $result;
    }
}
