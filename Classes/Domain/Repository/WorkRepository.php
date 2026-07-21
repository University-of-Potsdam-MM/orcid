<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Domain\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use JsonException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use UniPotsdam\Orcid\Dto\Person;
use UniPotsdam\Orcid\Dto\Work;
use UniPotsdam\Orcid\Dto\WorkType;
use UniPotsdam\Orcid\Value\OrcidId;
use UniPotsdam\Orcid\Value\OrcidIdCollection;

final class WorkRepository
{
    private Connection $connection;

    public function __construct(private readonly ConnectionPool $connectionPool)
    {
        $this->connection = $connectionPool->getConnectionForTable('tx_orcid_workdata');
    }

    /**
     * @throws Exception
     */
    public function findLastModifiedMap(OrcidId $orcidId): array
    {
        $queryBuilder = $this->queryBuilder();
        $expr = $queryBuilder->expr();

        $rows = $queryBuilder
            ->select('put_code', 'last_modified')
            ->from('tx_orcid_workdata')
            ->where(
                $expr->eq(
                    'orcid_id',
                    $queryBuilder->createNamedParameter((string)$orcidId)
                ),
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $map = [];
        foreach ($rows as $row) {
            $putCode = (int)$row['put_code'];
            $lastModified = (int)$row['last_modified'];
            $map[$putCode] = $lastModified;
        }

        return $map;
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function upsertWork(Work $work): void
    {
        $now = time();

        $authorsJson = json_encode(array_map(
            fn(Person $a) => ['given' => $a->getGivenName(), 'family' => $a->getFamilyName()],
            $work->getContributors()
        ), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $row = [
            'tstamp' => $now,
            'orcid_id' => (string)$work->getOrcidId(),
            'put_code' => $work->getPutCode(),
            'work_date' => $work->getWorkYear(),
            'work_month' => $work->getWorkMonth() ?? 0,
            'title' => $work->getTitle(),
            'work_type' => $work->getWorkType()->value,
            'doi' => $work->getDoi() ?? '',
            'doi_url' => $work->doiUrl() ?? '',
            'work_url' => $work->getWorkUrl() ?? '',
            'journal_title' => $work->getJournalTitle() ?? '',
            'authors' => $authorsJson ?: '[]',
            'raw_data' => $work->getRawDataAsJson() ?: '{}',
            'last_modified' => $work->getLastModified(),
            'citation_type' => $work->getCitationType() ?? '',
            'citation_value' => $work->getCitationValue() ?? '',
        ];

        $affected = $this->connection->update(
            'tx_orcid_workdata',
            $row,
            [
                'orcid_id' => $work->getOrcidId()->getValue(),
                'put_code' => $work->getPutCode(),
            ]
        );

        if ($affected === 0) {
            $row['crdate'] = $now;
            $this->connection->insert('tx_orcid_workdata', $row);
        }
    }

    /**
     * Delete local rows for this ORCID iD whose put-code is NOT in the given remote snapshot.
     *
     * @param int[] $keepPutCodes
     *
     * @throws Exception
     */
    public function deleteStale(OrcidId $orcidId, array $keepPutCodes): int
    {
        $queryBuilder = $this->queryBuilder();
        $expr = $queryBuilder->expr();

        $queryBuilder
            ->delete('tx_orcid_workdata')
            ->where(
                $expr->eq(
                    'orcid_id',
                    $queryBuilder->createNamedParameter((string)$orcidId)
                ),
            );

        if ($keepPutCodes !== []) {
            $queryBuilder->andWhere(
                $expr->notIn(
                    'put_code',
                    $queryBuilder->createNamedParameter(
                        array_values(array_map('intval', $keepPutCodes)),
                        ArrayParameterType::INTEGER
                    )
                ),
            );
        }

        return (int)$queryBuilder->executeStatement();
    }

    /**
     * @throws JsonException
     * @throws Exception
     *
     * @return Work[]
     */
    public function findByOrcidIds(OrcidIdCollection $orcidIds): array
    {
        if ($orcidIds->toStringArray() === []) {
            return [];
        }

        $queryBuilder = $this->queryBuilder();
        $expr = $queryBuilder->expr();

        $rows = $queryBuilder
            ->select('*')
            ->from('tx_orcid_workdata')
            ->where(
                $expr->in(
                    'orcid_id',
                    $queryBuilder->createNamedParameter($orcidIds->toStringArray(), ArrayParameterType::STRING)
                ),
            )
            ->orderBy('work_date', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        $result = [];
        $index = 0;

        foreach ($rows as $r) {
            $authors = [];
            $authorsArr = json_decode((string)($r['authors'] ?? '[]'), true, 512, JSON_THROW_ON_ERROR);
            if (is_array($authorsArr)) {
                foreach ($authorsArr as $a) {
                    $authors[] = new Person(
                        givenName: $a['given'] ?? null,
                        familyName: $a['family'] ?? null
                    );
                }
            }

            $raw = json_decode((string)($r['raw_data'] ?? '[]'), true, 512, JSON_THROW_ON_ERROR);
            $raw = is_array($raw) ? $raw : [];

            $result[$index] = new Work(
                orcidId: OrcidId::fromString($r['orcid_id']),
                putCode: $r['put_code'],
                title: (string)$r['title'],
                type: WorkType::fromOrcid($r['work_type']),
                year: $r['work_date'] !== null ? (int)$r['work_date'] : null,
                month: $r['work_month'] !== null ? (int)$r['work_month'] : 0,
                journalTitle: $r['journal_title'] ?: null,
                doi: $r['doi'] ?: null,
                workUrl: $r['work_url'] ?: null,
                contributors: $authors,
                lastModified: $r['last_modified'],
                raw: $raw,
                citationType: $r['citation_type'],
                citationValue: $r['citation_value'],
            );
            $index++;
        }

        return $result;
    }

    private function queryBuilder(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable('tx_orcid_workdata');
    }
}
