<?php
namespace UniPotsdam\Orcid\Services\Database;
/**
 * -------------------------------------------------------------
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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
* This class contains the functions for the database access for table tx_orcid_workdata
*/
class WorkDataDatabaseService
{

    /**
     * Get the stored work data from the database for the given orcid ID.
     * @param string $orcidID orcid ID of the user
     * @return array the work put codes of the stored data
     */
    private static function getWorkPutCode(string $orcidID){

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_orcid_workdata');

        //Get all data from tx_orcid_workdata table
        $statement = $queryBuilder
            ->select('orcid_workput_code')
            ->from('tx_orcid_workdata')
            ->where($queryBuilder->expr()->eq('orcid_id', $queryBuilder->createNamedParameter($orcidID)))
            ->execute();

        $workPutCode = array();
        while ($row = $statement->fetch()) {
            $workPutCode[] = $row['orcid_workput_code'];
        }

        return $workPutCode;

    }

    /**
     * Insert and update work data in tx_orcid_workdata table
     * @param string $orcidID the orcid id of the user
     * @param array $workData array of the work data to be inserted or updated
     */
    public static function insertOrUpdateWorkData(string $orcidID, array $workData){

        $workPutCodes = self::getWorkPutCode($orcidID);
        foreach ($workData as $value) {

            $row = array('orcid_id' => $orcidID,
                'orcid_workput_code' => $value['id'],
                'orcid_work_date' => $value['year'],
                'orcid_work_data' => $value['data'],
                'orcid_work_type' => $value['type'],
                'orcid_last_modified' => $value['lastModified'],
                'tstamp' => time(),
                'crdate' => time());
            if (!empty($workPutCodes) && in_array($value['id'], $workPutCodes)){
                $updateData[] = $row;
            } else {
                $insertData[] = $row;
            }
        }

        //initialize connection. In this case no QueryBuilder is used,
        // because bulkInsert exists only for connection object.
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_orcid_workdata');
        //insert new data if some exist
        if(!empty($insertData)){
            $connection
                ->bulkInsert('tx_orcid_workdata',
                    $insertData,
                    ['orcid_id', 'orcid_workput_code',
                        'orcid_work_date', 'orcid_work_data', 'orcid_work_type', 'orcid_last_modified',
                        'tstamp', 'crdate']
                );
        }

        if(!empty($updateData)) {
            foreach ($updateData as $data) {
                //update according to orcid_workput_code
                $connection
                    ->update('tx_orcid_workdata',
                        ['orcid_work_date' => $data['orcid_work_date'],
                            'orcid_work_data' => $data['orcid_work_data'],
                            'orcid_work_type' => $data['orcid_work_type'],
                            'orcid_last_modified' => $data['orcid_last_modified'],
                            'tstamp' => $data['tstamp']],
                        ['orcid_id' => $data['orcid_id'],
                            'orcid_workput_code' => $data['orcid_workput_code']]
                    );
            }
        }

    }


    /**
     * Returns all workdata for an orcid id.
     * @param string $orcidID the orcid id of the user
     * @return array the stored work data
     */
    public static function getWorkData(string $orcidID){
        //Initialize query to get Orcid data from tx_orcid_maindata table
        $workDataQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_orcid_workdata');

        $statement = $workDataQueryBuilder
            ->select('orcid_work_data')
            ->from('tx_orcid_workdata')
            ->where($workDataQueryBuilder->expr()->eq('orcid_id', $workDataQueryBuilder->createNamedParameter($orcidID)))
            ->addOrderBy('orcid_work_date', 'DESC')
            ->execute();

        $workData = array();
        while ($row = $statement->fetch()) {
            $workData[] = json_decode($row['orcid_work_data']);
        }
        return $workData;
    }

    /**
     * Get the stored work put codes and modified date from the database for the given orcid ID.
     * @param string $orcidID orcid ID of the user
     * @return array the work put codes of the stored data
     */
    public static function getWorkputCodes(string $orcidID){
        //Initialize query to get Orcid data from tx_orcid_maindata table
        $workDataQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_orcid_workdata');

        $statement = $workDataQueryBuilder
            ->select('orcid_workput_code', 'orcid_last_modified')
            ->from('tx_orcid_workdata')
            ->where($workDataQueryBuilder->expr()->eq('orcid_id', $workDataQueryBuilder->createNamedParameter($orcidID)))
            ->execute();

        $workputCodes = array();
        while ($row = $statement->fetch()) {
            $workputCodes[$row['orcid_workput_code']] = $row['orcid_last_modified'];
        }
        return $workputCodes;
    }

    /**
     * Remove corresponding work data for the given ids
     * @param array $orcidIDs orcid ids for which the workdata are (not) deleted (see parameter $notIn)
     * @param bool $notIn if set to true, all entries not in $orcidIds are deleted
     */
    public static function removeWorkDataByOrcidIds(array $orcidIDs, bool $notIn = null){

        //Initialize query to get Orcid id data from tx_orcid_workdata table
        $workDataQueryBuilderOrcid = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_orcid_workdata');

        if($notIn){
            //Query for delete data from tx_orcid_workdata table not related to the given ids
            if(empty($orcidIDs)){
                //array is empty -> delete all
                $workDataQueryBuilderOrcid
                    ->delete('tx_orcid_workdata')
                    ->execute();
            } else {
                $workDataQueryBuilderOrcid
                    ->delete('tx_orcid_workdata')
                    ->where($workDataQueryBuilderOrcid->expr()
                        ->notIn('orcid_id', $workDataQueryBuilderOrcid->createNamedParameter($orcidIDs, Connection::PARAM_STR_ARRAY))
                    )
                    ->execute();
            }
        } else {
            //Query for delete data from tx_orcid_workdata table related to the given ids
            $workDataQueryBuilderOrcid
                ->delete('tx_orcid_workdata')
                ->where($workDataQueryBuilderOrcid->expr()
                    ->in('orcid_id', $workDataQueryBuilderOrcid->createNamedParameter($orcidIDs, Connection::PARAM_STR_ARRAY))
                )
                ->execute();
        }
    }

    /**
     * Remove corresponding work data for the given ids
     * @param $workputCode code to be deleted
     */
    public static function removeWorkData($workputCode){

        //Initialize query to get Orcid id data from tx_orcid_workdata table
        $workDataQueryBuilderOrcid = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_orcid_workdata');

        //Query for delete id related data from tx_orcid_workdata table
        $workDataQueryBuilderOrcid
            ->delete('tx_orcid_workdata')
            ->where($workDataQueryBuilderOrcid->expr()
                ->eq('orcid_workput_code', $workDataQueryBuilderOrcid->createNamedParameter($workputCode))
            )
           ->execute();
    }


    /**
     * Get the stored work years from the database for the given orcid ID.
     * @param string $orcidID orcid ID of the user
     * @return array the work years of the stored data
     */
    public static function getWorkYears(string $orcidID){
        //Initialize query to get Orcid data from tx_orcid_maindata table
        $workDataQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_orcid_workdata');

        $statement = $workDataQueryBuilder
            ->select('orcid_work_date')
            ->from('tx_orcid_workdata')
            ->where($workDataQueryBuilder->expr()->eq('orcid_id', $workDataQueryBuilder->createNamedParameter($orcidID)))
            ->groupBy('orcid_work_date')
            ->orderBy('orcid_work_date')
            ->execute();

        $workData = array();
        while ($row = $statement->fetch()) {
            $workData[] = $row['orcid_work_date'];
        }
        return $workData;
    }

    /**
     * Get the stored work types from the database for the given orcid ID.
     * @param string $orcidID orcid ID of the user
     * @return array the work types of the stored data
     */
    public static function getWorkTypes(string $orcidID){
        //Initialize query to get Orcid data from tx_orcid_maindata table
        $workDataQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_orcid_workdata');

        $statement = $workDataQueryBuilder
            ->select('orcid_work_type')
            ->from('tx_orcid_workdata')
            ->where($workDataQueryBuilder->expr()->eq('orcid_id', $workDataQueryBuilder->createNamedParameter($orcidID)))
            ->groupBy('orcid_work_type')
            ->orderBy('orcid_work_type', 'DESC')
            ->execute();

        $workData = array();
        while ($row = $statement->fetch()) {
            $workData[] = $row['orcid_work_type'];
        }
        return $workData;
    }
}

