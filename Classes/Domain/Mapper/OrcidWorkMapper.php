<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Domain\Mapper;

use UniPotsdam\Orcid\Dto\Person;
use UniPotsdam\Orcid\Dto\Work;
use UniPotsdam\Orcid\Dto\WorkType;
use UniPotsdam\Orcid\Value\OrcidId;

class OrcidWorkMapper
{
    public function map(OrcidId $orcidId, array $work, Person $person): Work
    {
        $putCode = (int)($work['put-code'] ?? 0);

        $title = (string)($work['title']['title']['value'] ?? '');
        $title = $title !== '' ? $title : '(untitled)';

        $type = WorkType::fromOrcid($work['type'] ?? null);

        $year = null;
        $yearValue = $work['publication-date']['year']['value'] ?? null;
        if (is_numeric($yearValue)) {
            $year = (int)$yearValue;
        }

        $month = null;
        $monthValue = $work['publication-date']['month']['value'] ?? null;
        if (is_numeric($monthValue)) {
            $month = (int)$monthValue;
        }

        $journalTitle = $work['journal-title']['value'] ?? null;
        $journalTitle = is_string($journalTitle) && $journalTitle !== '' ? $journalTitle : null;

        $citation = $work['citation'] ?? null;

        $citationType = null;
        $citationValue = null;

        if (is_array($citation)) {
            $citationType = isset($citation['citation-type']) ? (string)$citation['citation-type'] : null;
            $citationValue = isset($citation['citation-value']) ? (string)$citation['citation-value'] : null;
        }

        $doi = $this->extractDoi($work);
        $workUrl = $this->extractUrl($work);

        $contributors = $this->extractContributors($work, $person);

        $lastModified = $this->extractLastModified($work);

        return new Work(
            orcidId: $orcidId,
            putCode: $putCode,
            title: $title,
            type: $type,
            year: $year,
            month: $month,
            journalTitle: $journalTitle,
            doi: $doi,
            workUrl: $workUrl,
            contributors: $contributors,
            lastModified: $lastModified,
            raw: $work,
            citationType: $citationType,
            citationValue: $citationValue,
        );
    }

    private function extractDoi(array $work): ?string
    {
        foreach (($work['external-ids']['external-id'] ?? []) as $ext) {
            if (($ext['external-id-type'] ?? '') === 'doi') {
                $value = $ext['external-id-normalized'] ?? null;

                return is_string($value) && $value !== '' ? $value : null;
            }
        }

        return null;
    }

    private function extractUrl(array $work): ?string
    {
        $url = $work['url']['value'] ?? null;

        return is_string($url) && $url !== '' ? $url : null;
    }

    /**
     * @return Person[]
     */
    private function extractContributors(array $work, Person $person): array
    {
        $contributors = $work['contributors']['contributor'] ?? null;
        if (!is_array($contributors) || $contributors === []) {
            return [$person];
        }

        $result = [];
        foreach ($contributors as $contributor) {
            $name = $contributor['credit-name']['value'] ?? null;
            if (!is_string($name)) {
                continue;
            }

            $name = trim($name);
            if ($name === '') {
                continue;
            }

            [$given, $family] = $this->splitDisplayName($name);
            $result[] = new Person(givenName: $given, familyName: $family);
        }

        if ($result === []) {
            return [$person];
        }

        return $result;
    }

    /**
     * Split an ORCID credit-name display string into [given, family].
     * Logic mirrors the legacy ext:orcid Helper::formatAuthor().
     *
     * @return array{0:string,1:string}
     */
    private function splitDisplayName(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return ['', ''];
        }

        // "Family, Given"
        if (str_contains($name, ',')) {
            $parts = explode(',', $name, 2);
            return [trim($parts[1] ?? ''), trim($parts[0])];
        }

        // Split on the last whitespace: [head, tail]
        $split = preg_split('/\s(?=\S+$)/', $name);
        if (!is_array($split) || count($split) < 2) {
            return ['', $name];
        }

        [$head, $tail] = $split;

        // Last token is uppercase — treated as initials ("Schmidt M" / "Schmidt MA")
        if (ctype_upper($tail)) {
            return [$tail, $head];
        }

        // Contains a common family-name particle — family starts at the particle
        $particles = ['von', 'zu', 'vom', 'zum', 'di', 'de', 'del', 'da', 'degli', 'dalla', 'van', 'ter'];
        $lowerTokens = array_map('strtolower', explode(' ', $name));
        $match = array_intersect($lowerTokens, $particles);
        if ($match !== []) {
            $particle = reset($match);
            $family = stristr($name, $particle);
            $given = stristr($name, $particle, true);
            if (is_string($family) && is_string($given)) {
                return [trim($given), trim($family)];
            }
        }

        // Default: "Given Family"
        return [$head, $tail];
    }

    private function extractLastModified(array $work): int
    {
        $value = $work['last-modified-date']['value'] ?? 0;
        if (is_numeric($value)) {
            return (int)$value;
        }

        return 0;
    }
}
