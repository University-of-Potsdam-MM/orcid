<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Domain\Repository;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use UniPotsdam\Orcid\Dto\Person;
use UniPotsdam\Orcid\Value\OrcidId;

final readonly class PersonProfileRepository
{
    public function __construct(private ConnectionPool $connectionPool) {}

    public function updateSyncStatus(OrcidId $orcidId, string $status, string $message = ''): void
    {
        $now = time();

        // 1) Ensure row exists (insert-first, ignore duplicate)
        try {
            $queryBuilder = $this->queryBuilder();
            $queryBuilder->insert('tx_orcid_maindata')
                ->values([
                    'orcid_id' => (string)$orcidId,
                    'last_sync_at' => 0,
                    'last_sync_status' => '',
                    'last_sync_message' => '',
                ])
                ->executeStatement();
        } catch (UniqueConstraintViolationException) {
            // exists already (or inserted concurrently) -> continue to update below
        }

        // 2) Update status fields
        $queryBuilder = $this->queryBuilder();
        $queryBuilder->update('tx_orcid_maindata')
            ->where(
                $queryBuilder->expr()->eq(
                    'orcid_id',
                    $queryBuilder->createNamedParameter((string)$orcidId)
                )
            )
            ->set('last_sync_at', $now)
            ->set('last_sync_status', $status)
            ->set('last_sync_message', $message)
            ->executeStatement();
    }

    public function upsertProfile(OrcidId $orcidId, Person $person): void
    {
        // Insert-first, update on duplicate
        try {
            $queryBuilder = $this->queryBuilder();
            $queryBuilder->insert('tx_orcid_maindata')
                ->values([
                    'orcid_id' => (string)$orcidId,
                    'given_name' => $person->getGivenName(),
                    'family_name' => $person->getFamilyName(),
                ])
                ->executeStatement();

            return;
        } catch (UniqueConstraintViolationException) {
            // exists already (or inserted concurrently) -> continue to update below
        }

        $queryBuilder = $this->queryBuilder();
        $queryBuilder->update('tx_orcid_maindata')
            ->where(
                $queryBuilder->expr()->eq(
                    'orcid_id',
                    $queryBuilder->createNamedParameter((string)$orcidId)
                )
            )
            ->set('given_name', $person->getGivenName())
            ->set('family_name', $person->getFamilyName())
            ->executeStatement();
    }

    /**
     * @throws Exception
     */
    public function findPerson(OrcidId $orcidId): ?Person
    {
        $queryBuilder = $this->queryBuilder();
        $expr = $queryBuilder->expr();

        $person = $queryBuilder
            ->select('given_name', 'family_name')
            ->from('tx_orcid_maindata')
            ->where(
                $expr->eq(
                    'orcid_id',
                    $queryBuilder->createNamedParameter((string)$orcidId)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        return new Person($person['given_name'] ?? '', $person['family_name'] ?? '');
    }

    private function queryBuilder(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable('tx_orcid_maindata');
    }
}
