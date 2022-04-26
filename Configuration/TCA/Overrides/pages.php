<?php

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
 * --------------------------------------------------------------
 */

defined('TYPO3_MODE') || die();

call_user_func(function()
{
    /**
     * Temporary variables
     */
    $extensionKey = 'orcid';

    /**
     * Default TypoScript for OrcidData
     */
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        $extensionKey,
        'Configuration/TypoScript',
        'ORCID Data'
    );

    /**
     * Default PageTS for OrcidData
     */
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
        $extensionKey,
        'Configuration/TsConfig/Page/NewContentElement.tsconfig',
        'ORCID Data'
    );

});
