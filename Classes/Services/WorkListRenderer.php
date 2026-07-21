<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Services;

use JsonException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use UniPotsdam\Orcid\Domain\Mapper\WorkToCslItemMapper;
use UniPotsdam\Orcid\Dto\Work;
use UniPotsdam\Orcid\Dto\WorkList;

final readonly class WorkListRenderer
{
    public function __construct(
        private WorkToCslItemMapper $cslMapper
    ) {}

    /**
     * @throws JsonException
     */
    public function buildClientRenderPayload(
        WorkList $workList,
        string $grouping,
        string $styleName,
        string $locale,
        string $styleBaseUrl,
        string $localeBaseUrl
    ): array {
        $groups = match ($grouping) {
            'year' => $workList->groupByYearDesc(),
            'type' => $workList->groupByType(),
            default => ['' => $workList->getWorks()],
        };

        // Map works to CSL per group
        $clientGroups = [];
        foreach ($groups as $key => $group) {
            $items = [];

            /** @var Work $work */
            foreach ($group as $idx => $work) {
                $items[] = [
                    'index' => $idx,
                    'item' => $this->cslMapper->map($work),
                ];
            }

            $clientGroups[] = [
                'key' => $key,
                'label' => $this->labelForGroupKey($grouping, $key),
                'items' => $items,
            ];
        }

        $groupsJson = json_encode([
            'styleName' => $styleName,
            'locale' => $locale,
            'styleBaseUrl' => rtrim($styleBaseUrl, '/'),
            'localeBaseUrl' => rtrim($localeBaseUrl, '/'),
            'groups' => $clientGroups,
        ], JSON_THROW_ON_ERROR);

        return [
            'personDisplayName' => $workList->getPersonDisplayName(),
            'groupsJson' => $groupsJson,
        ];
    }

    private function labelForGroupKey(string $grouping, $key): string
    {
        if ($grouping === 'year') {
            $y = (int)$key;

            return $y > 0 ? (string)$y : 'Unknown';
        }

        if ($grouping === 'type') {
            return LocalizationUtility::translate($key, 'orcid');
        }

        return '';
    }
}
