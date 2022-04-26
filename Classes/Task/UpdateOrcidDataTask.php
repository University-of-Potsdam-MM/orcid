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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use UniPotsdam\Orcid\Helper\Helper;
use UniPotsdam\Orcid\Services\Database\MainDataDatabaseService;
use UniPotsdam\Orcid\Services\Database\WorkDataDatabaseService;
use UniPotsdam\Orcid\Services\OrcidAPIService;

class UpdateOrcidDataTask extends AbstractTask {

	public function execute() {

        $ignoreTimestamps = $this->ignoreTimestamps;

	    $orcidIds = MainDataDatabaseService::getOrcidIDs(null);
	    if(!empty($orcidIds)) {
            $orcidData = OrcidAPIService::getAllOrcidData($orcidIds);
            //store id and content in tx_orcid_maindata
            MainDataDatabaseService::insertOrUpdateOrcidData($orcidData);

            foreach ($orcidData as $orcidID => $data) {

                $jsonContent = Helper::prepareWorkdata($data);

                //delete all entries which are not in the new data set
                $oldWorkputCodes = WorkDataDatabaseService::getWorkputCodes($orcidID);
                $workDataDeleted = false;
                foreach ($oldWorkputCodes as $workputCode => $tstamp) {
                    if (!array_key_exists($workputCode, $jsonContent)) {
                         WorkDataDatabaseService::removeWorkData($workputCode);
                         $workDataDeleted = true;
                    }
                }

                //get data from orcid api
                $workData = OrcidAPIService::getOrcidWorkData($jsonContent, $oldWorkputCodes, $ignoreTimestamps);

                //various checks and cleanups
                $workData = Helper::enrichWorkData($workData, $jsonContent['authorNames']);
                //stores data
                WorkDataDatabaseService::insertOrUpdateWorkData($orcidID, $workData);

                if(!empty($workData) || $workDataDeleted) {
                    // remove page from cache
                    //Initialize query to get Orcid id data from tt_content table
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
                    $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class)->removeByType(DeletedRestriction::class);

                    //Get all Orcid ids from tt_content table where the entries are not deleted
                    $pidStatement = $queryBuilder
                        ->select('pid')
                        ->from('tt_content')->where(
                            $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0)),
                            $queryBuilder->expr()->eq('orcid_id', $queryBuilder->createNamedParameter($orcidID))
                        )->groupBy('pid')
                        ->execute();

                    $pids = array();
                    while ($row = $pidStatement->fetch()) {
                        $pids[] = $row['pid'];
                    }
                    foreach ($pids as $pid) {
                        GeneralUtility::makeInstance(CacheManager::class)
                            ->flushCachesInGroupByTags('pages', ['pageId_' . $pid]);
                    }
                }

            }
        }
        return true;
    }
}
