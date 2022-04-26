<?php

namespace UniPotsdam\Orcid\Hooks;

/**
 * --------------------------------------------------------------
 * This file is part of the package UniPotsdam\Orcid.
 * copyright 2022 by University Potsdam
 * https://www.uni-potsdam.de/
 *
 * GitHub repo: https://github.com/University-of-Potsdam-MM/orcid
 *
 * Project: Orcid Extension
 * Developer: Stefanie Lemcke (stefanie.lemcke@uni-potsdam.de)
 * --------------------------------------------------------------
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use UniPotsdam\Orcid\Helper\ErrorCodes;
use UniPotsdam\Orcid\Helper\Helper;
use UniPotsdam\Orcid\Services\Database\MainDataDatabaseService;
use UniPotsdam\Orcid\Services\Database\WorkDataDatabaseService;
use UniPotsdam\Orcid\Services\OrcidAPIService;


/**
 * This hook saves the orcid data in the typo3 database after creating or updating the orcid content element.
 */
class SaveOrcidDataHook {

    public function processDatamap_postProcessFieldArray($status, $table, $id, array &$fieldArray, \TYPO3\CMS\Core\DataHandling\DataHandler &$pObj)
    {


        // new orcid content element
        if ($status == 'new' && $fieldArray['CType'] == 'uporcidext') {
            //get orcid id
            $orcidId = $fieldArray['orcid_id'];
            // update orcid content element
        } else if ($status == 'update' && $table == 'tt_content'
            && BackendUtility::getRecord('tt_content', $id)['CType'] == 'uporcidext'
            && (!array_key_exists('CType', $fieldArray) || $fieldArray['CType']== 'uporcidext')) {
            //get orcid id
            if(array_key_exists('orcid_id', $fieldArray)){
                $orcidId = $fieldArray['orcid_id'];
            } else {
                $orcidId = BackendUtility::getRecord('tt_content', $id)['orcid_id'];
            }
        } else {
            //ignore all other content elements and stati
            return;
        }

        if (!is_null($orcidId) && Helper::checkFormat($orcidId)) {
            //retrieve data from api and stores it in the database
            $orcidData = OrcidAPIService::getOrcidData($orcidId);
            if ($orcidData instanceof ErrorCodes) {
                if($orcidData->equals(ErrorCodes::cast(ErrorCodes::WRONG_HTTP_CODE))){
                    $error = LocalizationUtility::translate('LLL:EXT:orcid/Resources/Private/Language/locallang_be.xlf:noRecordError');
                    $hint = LocalizationUtility::translate('LLL:EXT:orcid/Resources/Private/Language/locallang_be.xlf:noRecordError.hint');

                } else {
                    $error = LocalizationUtility::translate('LLL:EXT:orcid/Resources/Private/Language/locallang_be.xlf:error');
                    $hint = LocalizationUtility::translate('LLL:EXT:orcid/Resources/Private/Language/locallang_be.xlf:error.hint');
                }
                Helper::errorMessage($error, $hint);
            } else {
                //store id and content in tx_orcid_maindata
                MainDataDatabaseService::insertOrUpdateOrcidData($orcidData);

                $jsonContent = Helper::prepareWorkdata($orcidData[$orcidId]);

                //delete all entries which are not in the new data set
                $oldWorkputCodes = WorkDataDatabaseService::getWorkputCodes($orcidId);
                foreach ($oldWorkputCodes as $workputCode => $tstamp) {
                    if (!array_key_exists($workputCode, $jsonContent)) {
                        WorkDataDatabaseService::removeWorkData($workputCode);
                    }
                }

                //get data form orcid api
                $workData = OrcidAPIService::getOrcidWorkData($jsonContent, $oldWorkputCodes);

                //various checks and cleanups
                $workData = Helper::enrichWorkData($workData, $jsonContent['authorNames']);

                //stores data
                WorkDataDatabaseService::insertOrUpdateWorkData($orcidId, $workData);
                $success = LocalizationUtility::translate('LLL:EXT:orcid/Resources/Private/Language/locallang_be.xlf:success');
                $hint = LocalizationUtility::translate('LLL:EXT:orcid/Resources/Private/Language/locallang_be.xlf:success.hint');
                Helper::successMessage($success, $hint . $orcidId);
            }
        }
    }
}

