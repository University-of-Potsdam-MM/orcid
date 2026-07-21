<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Services;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use UniPotsdam\Orcid\Value\OrcidId;

readonly class OrcidIdProvider
{
    public function __construct(private ConnectionPool $connectionPool) {}

    /**
     * @throws Exception
     * @return string[]
     */
    public function findAllOrcidIds(): array
    {
        $queryBuilder = $this->queryBuilderFor();

        $expr = $queryBuilder->expr();

        $queryBuilder->select('orcid_id')
            ->from('tx_orcid_maindata')
            ->where(
                $expr->neq('orcid_id', $queryBuilder->createNamedParameter('')),
            )
            ->orderBy('orcid_id', 'ASC');

        $rows = $queryBuilder->executeQuery()->fetchFirstColumn();

        $valid = [];
        foreach ($rows as $raw) {
            if ($raw === '') {
                continue;
            }

            $orcidId = OrcidId::fromString($raw);
            $valid[(string)$orcidId] = $orcidId;
        }

        ksort($valid);

        return array_values($valid);
    }

    /**
     * @throws Exception
     * @return string[]
     */
    public function findAllNewOrcidIds(): array
    {
        $queryBuilder = $this->queryBuilderForMainData();

        $expr = $queryBuilder->expr();

        $queryBuilder->select('orcid_id')
            ->from('tx_orcid_maindata')
            ->where(
                $expr->eq('last_sync_at', $queryBuilder->createNamedParameter(0, ParameterType::INTEGER)),
                $expr->eq('last_sync_status', $queryBuilder->createNamedParameter('new')),
            )
            ->orderBy('orcid_id', 'ASC');

        $rows = $queryBuilder->executeQuery()->fetchFirstColumn();

        $valid = [];
        foreach ($rows as $raw) {
            if ($raw === '') {
                continue;
            }

            $orcidId = OrcidId::fromString($raw);
            $valid[(string)$orcidId] = $orcidId;
        }

        ksort($valid);

        return array_values($valid);
    }

    private function queryBuilderFor(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable('tt_content');
    }

    private function queryBuilderForMainData(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable('tx_orcid_maindata');
    }
}
