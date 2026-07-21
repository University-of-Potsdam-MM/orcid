<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Hooks;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use UniPotsdam\Orcid\Value\OrcidIdCollection;

/**
 * This hook saves the orcid_id in the typo3 database if it doesn't exist yet in the DB.
 */
class SaveOrcidDataHook
{
    /**
     * @param mixed $id
     *
     * @throws Exception
     */
    public function processDatamap_postProcessFieldArray(
        string $status,
        string $table,
        $id,
        array &$fieldArray,
        DataHandler $pObj
    ): void {
        if ($table !== 'tt_content') {
            return;
        }

        $uid = $this->resolveUid($status, (string)$id, $pObj);
        if ($uid <= 0) {
            return;
        }

        // Determine ORCID from submitted values or from DB (when unchanged fields are not in $fieldArray)
        $rawOrcid = isset($fieldArray['orcid_id']) ? (string)$fieldArray['orcid_id'] : null;

        $ttRow = null;
        if ($rawOrcid === null || trim($rawOrcid) === '') {
            $ttRow = $this->fetchTtContentRow($uid);
            $rawOrcid = (string)($ttRow['orcid_id'] ?? '');
        }
        if ($rawOrcid === '') {
            return;
        }

        // Only react for orcid_works content element
        $cType = (string)(($ttRow['CType'] ?? '') ?: ($fieldArray['CType'] ?? ''));
        if ($cType !== '' && $cType !== 'orcid_works') {
            return;
        }

        $orcidIds = OrcidIdCollection::fromCommaSeparatedList($rawOrcid);
        if ($orcidIds->isEmpty()) {
            return;
        }

        foreach ($orcidIds as $orcidId) {
            $this->insertMainDataIfMissing((string)$orcidId);
        }
    }

    private function resolveUid(string $status, string $id, DataHandler $dataHandler): int
    {
        if ($status === 'new' && str_starts_with($id, 'NEW')) {
            return (int)($dataHandler->substNEWwithIDs[$id] ?? 0);
        }

        return (int)$id;
    }

    /**
     * @throws Exception
     */
    private function fetchTtContentRow(int $uid): array
    {
        $queryBuilder = $this->queryBuilder('tt_content');

        return (array)$queryBuilder
            ->select('uid', 'CType', 'orcid_id')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, ParameterType::INTEGER)),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
    }

    /**
     * @throws Exception
     */
    private function insertMainDataIfMissing(string $orcid): void
    {
        $queryBuilder = $this->queryBuilder('tx_orcid_maindata');
        $existingUid = $queryBuilder
            ->select('uid')
            ->from('tx_orcid_maindata')
            ->where(
                $queryBuilder->expr()->eq(
                    'orcid_id',
                    $queryBuilder->createNamedParameter($orcid)
                )
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        if ($existingUid !== false) {
            return;
        }

        $queryBuilder = $this->queryBuilder('tx_orcid_maindata');
        $queryBuilder->insert('tx_orcid_maindata')
            ->values([
                'orcid_id' => $orcid,
                'last_sync_status' => 'new',
                'last_sync_message' => '',
            ])
            ->executeStatement();
    }

    private function queryBuilder(string $table): QueryBuilder
    {
        /** @var ConnectionPool $pool */
        $pool = GeneralUtility::makeInstance(ConnectionPool::class);

        return $pool->getQueryBuilderForTable($table);
    }
}
