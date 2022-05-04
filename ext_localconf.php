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
 *
 * --------------------------------------------------------------
 */

defined('TYPO3_MODE') || die();

/***************
 * PageTS
 */
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . 'orcid' . '/Configuration/TsConfig/Page/NewContentElement.tsconfig">'
);

$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);

// use same identifier as used in TSconfig for icon
$iconRegistry->registerIcon(
   // use same identifier as used in TSconfig for icon
   'orcid_icon',
   \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
   // font-awesome identifier ('external-link-square')
   ['source' => 'EXT:'.'orcid' .'/Resources/Public/Icons/orcid.png']
);



if (TYPO3_MODE === 'BE' )   {
  $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
  $pageRenderer->loadRequireJsModule('TYPO3/CMS/Orcid/info');
}



/***************
 * Register Scheduler task for Orcid Data
 */
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][UniPotsdam\Orcid\Task\UpdateOrcidDataTask::class] = array(
   'extension' => 'orcid',
   'title' => 'Update Orcid Data',
   'description' => 'Update Orcid data with timestamps older than one day',
   'additionalFields' => UniPotsdam\Orcid\Task\UpdateOrcidDataAdditionalFieldProvider::class

);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][UniPotsdam\Orcid\Task\DeleteOrcidDataTask::class] = array(
   'extension' => 'orcid',
   'title' => 'Delete Orcid Data',
   'description' => 'Delete Orcid data, if orcid id is not used anymore.',
);

/***************
 * Register for hook to show preview of tt_content element of CType="up_orcid_extension" in page module
 */
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['uporcidext'] = UniPotsdam\Orcid\Hooks\PageLayoutView\NewContentElement::class;

/***************
 * Register for hook to save orcid data in typo3 database after creating or updating the orcid content element
 */
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['uporcidext'] = UniPotsdam\Orcid\Hooks\SaveOrcidDataHook::class;

/***************
 * Configure the log file
 */
$GLOBALS['TYPO3_CONF_VARS']['LOG']['UniPotsdam']['Orcid']['writerConfiguration'] = [
    \TYPO3\CMS\Core\Log\LogLevel::WARNING => [
        \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
            'logFile' => \TYPO3\CMS\Core\Core\Environment::getVarPath() . '/log/orcid_error.log'
        ]
    ]
];

?>