<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Services;

use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Throwable;
use UniPotsdam\Orcid\Domain\Repository\PersonProfileRepository;
use UniPotsdam\Orcid\Domain\Repository\WorkRepository;
use UniPotsdam\Orcid\Services\Exception\OrcidNotFoundException;
use UniPotsdam\Orcid\Value\OrcidId;

readonly class OrcidSyncService
{
    public function __construct(
        private OrcidApiClient $client,
        private PersonProfileRepository $personProfileRepository,
        private WorkRepository $workRepository,
        private LoggerInterface $logger,
    ) {}

    public function syncOrcid(OrcidId $orcidId): void
    {
        $this->personProfileRepository->updateSyncStatus($orcidId, 'running', '');

        try {
            $this->doSync($orcidId);
        } catch (OrcidNotFoundException $e) {
            $this->logger->warning('ORCID iD not found, skipping.', [
                'orcid' => (string)$orcidId,
                'exception' => $e,
            ]);
            $this->personProfileRepository->updateSyncStatus(
                $orcidId,
                'notfound',
                'ORCID iD does not exist (HTTP 404)'
            );
        } catch (Throwable $e) {
            $this->logger->error('ORCID sync failed.', [
                'orcid' => (string)$orcidId,
                'exception' => $e,
            ]);
            $this->personProfileRepository->updateSyncStatus(
                $orcidId,
                'failed',
                $e->getMessage()
            );
        }
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    private function doSync(OrcidId $orcidId): void
    {
        // putCode => lastModified
        $existingWorks = $this->workRepository->findLastModifiedMap($orcidId);

        $workList = $this->client->fetchWorkList($orcidId, $existingWorks);

        $person = $workList->getPerson();
        $this->personProfileRepository->upsertProfile($orcidId, $person);

        foreach ($workList->getWorks() as $work) {
            $putCode = $work->getPutCode();

            // If local exists and remote lastModified is known and not newer, skip
            $localLastModified = $existingWorks[$putCode] ?? null;
            $remoteLastModified = $work->getLastModified();

            if ($localLastModified !== null && $remoteLastModified > 0 && $remoteLastModified <= $localLastModified) {
                continue;
            }
            $this->workRepository->upsertWork($work);
        }

        $prunedStale = $this->workRepository->deleteStale($orcidId, $workList->getRemotePutCodes());

        $message = 'Synced ' . count($workList->putCodes()) . ' works';
        if ($prunedStale > 0) {
            $message .= ', pruned ' . $prunedStale . ' stale';
        }
        $this->personProfileRepository->updateSyncStatus($orcidId, 'ok', $message);
    }
}
