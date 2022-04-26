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
 * Developer: Stefanie Lemcke (stefanie.lemcke@uni-potsdam.de)
 *
 * --------------------------------------------------------------
 */

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;


class UpdateOrcidDataAdditionalFieldProvider implements AdditionalFieldProviderInterface {

    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule) {
        // Initialize checkbox
        if ($schedulerModule->CMD === 'edit') {
            $checked = $task->ignoreTimestamps === true ? 'checked="checked" ' : '';
        } else {
            $checked = '';
        }

        // Write the code for the field
        $fieldName = 'tx_scheduler[orcid_ignoreTimestamps]';
        $fieldId = 'orcid_ignoreTimestamps';
        $fieldHtml = '<div class="form-check"><input class="form-check-input" type="checkbox" ' . $checked . ' name="' . $fieldName . '" id="' . $fieldId . '"></div>';
        $additionalFields[$fieldId] = array(
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_be.xlf:scheduler.ignoreTimestamp',
            'cshKey' => '_MOD_tools_txschedulerM1',
            'cshLabel' => $fieldId
        );
        return $additionalFields;
    }

    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule) {
        $validData = false;
        if (!isset($submittedData['orcid_ignoreTimestamps'])) {
            $validData = true;
        } elseif ($submittedData['orcid_ignoreTimestamps'] === 'on') {
            $validData = true;
        }
        return $validData;
    }

    public function saveAdditionalFields(array $submittedData, AbstractTask $task) {
        $task->ignoreTimestamps = ($submittedData['orcid_ignoreTimestamps'] ?? '') === 'on';
    }

}
