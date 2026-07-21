<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Controller;

use Doctrine\DBAL\Exception;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileException;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use UniPotsdam\Orcid\Domain\Repository\PersonProfileRepository;
use UniPotsdam\Orcid\Domain\Repository\WorkRepository;
use UniPotsdam\Orcid\Dto\WorkList;
use UniPotsdam\Orcid\Services\WorkListRenderer;
use UniPotsdam\Orcid\Value\OrcidIdCollection;

class OrcidWorkController extends ActionController
{
    public function __construct(
        private readonly WorkRepository $workRepository,
        private readonly PersonProfileRepository $personProfileRepository,
        private readonly WorkListRenderer $workListRenderer,
        private readonly AssetCollector $assetCollector,
    ) {}

    /**
     * Display ORCID works.
     *
     * @throws Exception
     * @throws JsonException
     * @throws InvalidFileException
     */
    public function listAction(): ResponseInterface
    {
        // Get data from content element fields
        $contentObjectData = $this->request->getAttribute('currentContentObject')?->data ?? [];

        $orcidId = $contentObjectData['orcid_id'] ?? '';
        $citationStyle = $contentObjectData['orcid_citation_style'] ?? '';
        $groupingStyle = (int)($contentObjectData['orcid_grouping_style'] ?? 0);
        $showProfileOwner = (bool)($contentObjectData['orcid_profile_owner'] ?? 1);

        $grouping = match ($groupingStyle) {
            1 => 'year',
            2 => 'type',
            default => 'none',
        };

        $this->assetCollector->addJavaScript(
            'orcid-citation-js',
            'EXT:orcid/Resources/Public/JavaScript/Vendor/citation-js.bundle.min.js'
        );

        $this->assetCollector->addJavaScript(
            'orcid-citeproc',
            'EXT:orcid/Resources/Public/JavaScript/citeproc.min.js'
        );

        $this->assetCollector->addJavaScript(
            'orcid-citations',
            'EXT:orcid/Resources/Public/JavaScript/orcid-citations.min.js'
        );

        $this->assetCollector->addStyleSheet(
            'orcid-stylesheet',
            'EXT:orcid/Resources/Public/StyleSheets/OrcidStyles.css'
        );

        $styleBaseUrl = PathUtility::getPublicResourceWebPath('EXT:orcid/Resources/Public/CitationStyleLanguage/styles');
        $localeBaseUrl = PathUtility::getPublicResourceWebPath('EXT:orcid/Resources/Public/CitationStyleLanguage/locales');

        $orcidIds = OrcidIdCollection::fromOrcidIds();
        $works = [];
        $person = null;

        if ($orcidId !== '') {
            try {
                $orcidIds = OrcidIdCollection::fromCommaSeparatedList($orcidId);
            } catch (InvalidArgumentException $e) {
                // Invalid ORCID ID format - use empty collection
                // The template will show the "not found" message
            }
        }

        if (!$orcidIds->isEmpty()) {
            $works = $this->workRepository->findByOrcidIds($orcidIds);

            // Only show a single person header if exactly one ID
            if ($showProfileOwner && $orcidIds->count() === 1) {
                $singleOrcidId = $orcidIds->getFirst();
                $person = $singleOrcidId ? $this->personProfileRepository->findPerson($singleOrcidId) : null;
            }
        }

        $workList = new WorkList(
            orcidIds: $orcidIds,
            person: $person,
            works: $works
        );

        /** @var SiteLanguage $language */
        $language = $this->request->getAttribute('language');
        $locale = str_replace('_', '-', $language->getLocale()->getName());

        $payload = $this->workListRenderer->buildClientRenderPayload(
            workList: $workList,
            grouping: $grouping,
            styleName: $citationStyle,
            locale: $locale,
            styleBaseUrl: $styleBaseUrl,
            localeBaseUrl: $localeBaseUrl
        );
        $this->view->assignMultiple([
            'uid' => $contentObjectData['uid'],
            'personDisplayName' => $payload['personDisplayName'], // renderer can decide empty for multi
            'groupsJson' => $payload['groupsJson'],
        ]);

        return $this->htmlResponse();
    }
}
