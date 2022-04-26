<?php
namespace UniPotsdam\Orcid\Task;



/**
 * --------------------------------------------------------------
 * This file is part of the package UniPotsdam\Orcid.
 * copyright 2022 by University Potsdam
 * https://www.uni-potsdam.de/
 *
 * GitHub repo: https://github.com/University-of-Potsdam-MM/orcid
 *
 * Project: Orcid Extension
 * Developer: Anuj Sharma (asharma@uni-potsdam.de)
 * Developer: Stefanie Lemcke (stefanie.lemcke@uni-potsdam.de)
 *
 * --------------------------------------------------------------
 */


use UniPotsdam\Orcid\Services\Database\MainDataDatabaseService;
use UniPotsdam\Orcid\Services\Database\WorkDataDatabaseService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Scheduler\Task\AbstractTask;


class DeleteOrcidDataTask extends AbstractTask {
	

	public function execute() {
		
		//Initialize query to get Orcid id data from tt_content table
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
		$queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class)->removeByType(DeletedRestriction::class);

		//Get all Orcid ids from tt_content table where the entries are not deleted
        $activeStatement = $queryBuilder
            ->select('orcid_id')
            ->from('tt_content')->where(
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0)),
                $queryBuilder->expr()->neq('orcid_id', $queryBuilder->createNamedParameter(''))
            )->execute();

        $activeOrcidIDs= array();
        while ($row = $activeStatement->fetch()) {
            $activeOrcidIDs[] = $row['orcid_id'];
        }

        MainDataDatabaseService::removeOrcidData($activeOrcidIDs, True);
        WorkDataDatabaseService::removeWorkDataByOrcidIds($activeOrcidIDs, true);

        return true;
    }
    

}
