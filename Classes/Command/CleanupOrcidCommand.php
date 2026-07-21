<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Command;

use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CleanupOrcidCommand extends Command
{
    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        /** @var Connection $ttContentConnection */
        $ttContentConnection = $connectionPool->getConnectionForTable('tt_content');

        // Get all orcid IDs used in the orcid_works CE
        $rows = $ttContentConnection->select(
            ['orcid_id'],
            'tt_content',
            [
                'CType' => 'orcid_works',
                'deleted' => 0,
            ]
        )->fetchAllAssociative();

        $usedOrcidIds = [];

        foreach ($rows as $row) {
            if (empty($row['orcid_id'])) {
                continue;
            }

            foreach (explode(',', $row['orcid_id']) as $id) {
                $id = trim($id);
                if ($id !== '') {
                    $usedOrcidIds[$id] = true;
                }
            }
        }

        $usedOrcidIds = array_keys($usedOrcidIds);

        $mainConnection = $connectionPool->getConnectionForTable('tx_orcid_maindata');
        $workConnection = $connectionPool->getConnectionForTable('tx_orcid_workdata');

        $queryBuilderMainData = $mainConnection->createQueryBuilder();
        $queryBuilderWorkData = $workConnection->createQueryBuilder();

        if (!empty($usedOrcidIds)) {
            $queryBuilderMainData
                ->delete('tx_orcid_maindata')
                ->where(
                    $queryBuilderMainData->expr()->notIn(
                        'orcid_id',
                        $queryBuilderMainData->createNamedParameter($usedOrcidIds, Connection::PARAM_STR_ARRAY)
                    )
                );

            $queryBuilderWorkData
                ->delete('tx_orcid_workdata')
                ->where(
                    $queryBuilderWorkData->expr()->notIn(
                        'orcid_id',
                        $queryBuilderWorkData->createNamedParameter($usedOrcidIds, Connection::PARAM_STR_ARRAY)
                    )
                );
        }

        $deletedMain = $queryBuilderMainData->executeStatement();
        $deletedWork = $queryBuilderWorkData->executeStatement();

        $output->writeln('<info>Cleanup finished</info>');
        $output->writeln('Deleted from tx_orcid_maindata: ' . $deletedMain);
        $output->writeln('Deleted from tx_orcid_workdata: ' . $deletedWork);

        return Command::SUCCESS;
    }
}
