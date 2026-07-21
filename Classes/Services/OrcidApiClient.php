<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Services;

use JsonException;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;
use UniPotsdam\Orcid\Domain\Mapper\OrcidPersonMapper;
use UniPotsdam\Orcid\Domain\Mapper\OrcidWorkMapper;
use UniPotsdam\Orcid\Dto\Person;
use UniPotsdam\Orcid\Dto\Work;
use UniPotsdam\Orcid\Dto\WorkList;
use UniPotsdam\Orcid\Services\Exception\OrcidApiException;
use UniPotsdam\Orcid\Services\Exception\OrcidNotFoundException;
use UniPotsdam\Orcid\Value\OrcidId;
use UniPotsdam\Orcid\Value\OrcidIdCollection;

/**
 * ORCID API Service implementing OrcidApiInterface
 * Handles all communication with ORCID API and converts to DTOs.
 */
class OrcidApiClient implements OrcidApiInterface
{
    private const string BASE = 'https://pub.orcid.org/v3.0/';
    private string $apiUrl;
    private string $proxy;

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly OrcidRateLimiter $rateLimiter,
        private readonly OrcidPersonMapper $personMapper,
        private readonly OrcidWorkMapper $workMapper,
        private readonly ?ExtensionConfiguration $extensionConfiguration = null
    ) {
        $configuration = $this->extensionConfiguration->get('orcid');
        $this->apiUrl = trim($configuration['apiurl'] ?? '');
        $this->proxy = trim($configuration['proxy'] ?? '');

        if (empty($this->apiUrl)) {
            $this->apiUrl = self::BASE;
        }
    }

    /**
     * @throws JsonException
     * @throws OrcidApiException
     */
    public function fetchWorkList(OrcidId $orcidId, array $localLastModifiedMap = []): WorkList
    {
        $person = $this->fetchPerson($orcidId);

        $works = $this->getJson($this->apiUrl . $orcidId . '/works');

        $remoteLastModifiedMap = $this->extractPutCodesWithLastModified($works);

        $worksToSync = [];

        // putCode => remoteLastModified (0 if missing)
        foreach ($remoteLastModifiedMap as $putCode => $remoteLastModified) {
            $localLastModified = $localLastModifiedMap[$putCode] ?? null;

            // If remote last-modified is known and local is up-to-date -> do not fetch detail
            if ($localLastModified !== null && $remoteLastModified > 0 && $remoteLastModified <= $localLastModified) {
                continue;
            }

            $worksToSync[] = $this->fetchWork($orcidId, $putCode, $person);
        }

        return new WorkList(
            OrcidIdCollection::fromOrcidIds($orcidId),
            $person,
            $worksToSync,
            array_keys($remoteLastModifiedMap),
        );
    }

    /**
     * @throws JsonException
     * @throws OrcidApiException
     */
    public function fetchWork(OrcidId $orcidId, int $putCode, Person $person): Work
    {
        $work = $this->getJson($this->apiUrl . $orcidId . '/work/' . $putCode);

        return $this->workMapper->map($orcidId, $work, $person);
    }

    /**
     * @throws JsonException
     * @throws OrcidApiException
     */
    public function fetchPerson(OrcidId $orcidId): Person
    {
        $person = $this->getJson($this->apiUrl . $orcidId . '/person');

        return $this->personMapper->map($person);
    }

    /**
     * @throws JsonException
     * @throws OrcidApiException
     */
    private function getJson(string $url): array
    {
        $this->rateLimiter->throttle();

        $response = $this->requestFactory->request($url, 'GET', [
            'headers' => [
                'Accept' => 'application/vnd.orcid+json',
                'User-Agent' => 'TYPO3 ORCID Extension',
            ],
            'timeout' => 20,
            'proxy' => $this->proxy !== '' ? $this->proxy : null,
            // Do not let Guzzle throw on 4xx/5xx; handle the status code ourselves below.
            'http_errors' => false,
        ]);

        return $this->decodeJson($response, $url);
    }

    /**
     * @throws JsonException
     * @throws OrcidApiException
     */
    private function decodeJson(ResponseInterface $response, string $url): array
    {
        $status = $response->getStatusCode();
        if ($status === 404) {
            throw new OrcidNotFoundException('ORCID resource not found (HTTP 404): ' . $url, 1700000002);
        }
        if ($status < 200 || $status >= 300) {
            throw new OrcidApiException('ORCID API error HTTP ' . $status . ': ' . $url, 1700000001);
        }

        $body = (string)$response->getBody();
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        return is_array($data) ? $data : [];
    }

    /**
     * @return array<int,int> putCode => lastModifiedMs(0 if unknown)
     */
    private function extractPutCodesWithLastModified(array $works): array
    {
        $result = [];

        // Each ORCID `group` represents one logical work
        // Pick the preferred work-summary (highest display-index) so sources contributing the same work are not stored as duplicates.
        foreach (($works['group'] ?? []) as $group) {
            $preferredPutCode = null;
            $preferredDisplayIndex = -1;
            $preferredLastModified = 0;

            foreach (($group['work-summary'] ?? []) as $workSummary) {
                if (!isset($workSummary['put-code'])) {
                    continue;
                }

                $displayIndex = (int)($workSummary['display-index'] ?? 0);
                if ($preferredPutCode !== null && $displayIndex <= $preferredDisplayIndex) {
                    continue;
                }

                $lastModifiedValue = $workSummary['last-modified-date']['value'] ?? 0;
                $preferredPutCode = (int)$workSummary['put-code'];
                $preferredDisplayIndex = $displayIndex;
                $preferredLastModified = is_numeric($lastModifiedValue) ? (int)$lastModifiedValue : 0;
            }

            if ($preferredPutCode !== null) {
                $result[$preferredPutCode] = $preferredLastModified;
            }
        }

        ksort($result);

        return $result;
    }
}
