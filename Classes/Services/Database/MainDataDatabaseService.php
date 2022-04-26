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
use UniPotsdam\Orcid\Helper\Helper;

/**
* This class contains the functions for the database access for table tx_orcid_maindata
*/
class MainDataDatabaseService
{

    /**
     * Retrieve (all) orcidIds. If an update time is given, all ids updated before this time will be returned.
     * @param string $orcidID [optional] the desired orcid id. If it is null, all orcid ids will be returned
     * @return mixed
     */
    public static function getOrcidIDs($orcidID = null){

        //Initialize query to get Orcid data from tx_orcid_maindata table
        $orcidQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_orcid_maindata');


        if(is_null($orcidID)){
            //Get all data from tx_orcid_maindata table
            $orcidData = $orcidQueryBuilder
                ->select('orcid_id')
                ->from('tx_orcid_maindata')
                ->execute()
                ->fetchAll();


        } else {
            //Get the data from tx_orcid_maindata table for the given orcidID
            $orcidData = $orcidQueryBuilder
                ->select('orcid_id')
                ->from('tx_orcid_maindata')
                ->where($orcidQueryBuilder->expr()->eq('orcid_id', $orcidQueryBuilder->createNamedParameter($orcidID)))
                ->execute();
            $orcidData = $orcidData->fetch();
        }

        return $orcidData;

    }

    /**
     * Insert or update Query for the given orcid data
     * @param array $content orcid data as array of id and data
     */
    public static function insertOrUpdateOrcidData(array $content) {

        foreach ($content as $orcidID => $orcidData) {

            $storedData = self::getOrcidIDs($orcidID);
            $authorName = Helper::getAuthor($orcidData);

            $orcidDataQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_orcid_maindata');
            if (is_null($storedData) || !$storedData) {
                //Insert Orcid Id in tx_orcid_maindata table
                $orcidDataQueryBuilder
                    ->insert('tx_orcid_maindata')
                    ->values(['orcid_id' => $orcidID, 'author_name' => $authorName, 'crdate' => time()])
                    ->execute();

            } else {
                //update according to orcid ID
                $orcidDataQueryBuilder
                    ->update('tx_orcid_maindata')
                    ->where(
                        $orcidDataQueryBuilder->expr()->eq('orcid_id', $orcidDataQueryBuilder->createNamedParameter($orcidID))
                    )
                    ->set('author_name', $authorName)
                    ->set('tstamp', time())
                    ->execute();

            }
        }
    }

    /**
     * Gets the author name of an orcid record
     * @param string $orcidID
     */
    public static function getAuthorName(string $orcidID){

        //Initialize query to get Orcid author from tx_orcid_maindata table
        $orcidQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_orcid_maindata');
        $authorName = $orcidQueryBuilder
            ->select('author_name')
            ->from('tx_orcid_maindata')
            ->where($orcidQueryBuilder->expr()->eq('orcid_id', $orcidQueryBuilder->createNamedParameter($orcidID)))
            ->execute()
            ->fetch();
        return $authorName['author_name'];
    }

    /**
     * Remove orcid ids
     * @param array $orcidIDs orcid ids for which are (not) deleted (see parameter $notIn)
     * @param bool $notIn if set to true, all entries not in $orcidIds are deleted
     */
    public static function removeOrcidData(array $orcidIDs, bool $notIn = null){

        //Initialize query to get Orcid id data from tx_orcid table
        $orcidQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_orcid_maindata');

        if($notIn){
            //Query for delete data from tx_orcid_maindata table not related to the given ids
            if(empty($orcidIDs)){
                //array is empty -> delete all
                $orcidQueryBuilder
                    ->delete('tx_orcid_maindata')
                    ->execute();
            } else {
                $orcidQueryBuilder
                    ->delete('tx_orcid_maindata')
                    ->where($orcidQueryBuilder->expr()
                        ->notIn('orcid_id', $orcidQueryBuilder->createNamedParameter($orcidIDs, Connection::PARAM_STR_ARRAY))
                    )
                    ->execute();
            }
        } else {
            //Query for delete data from tx_orcid_maindata table related to the given ids
            $orcidQueryBuilder
                ->delete('tx_orcid_maindata')
                ->where($orcidQueryBuilder->expr()
                    ->in('orcid_id', $orcidQueryBuilder->createNamedParameter($orcidIDs, Connection::PARAM_STR_ARRAY))
                )
                ->execute();
        }
    }
}


