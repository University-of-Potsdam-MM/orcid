<?php

declare(strict_types=1);

use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\Enum\Interval;
use TYPO3\CMS\Core\Log\Writer\RotatingFileWriter;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use UniPotsdam\Orcid\Controller\OrcidWorkController;
use UniPotsdam\Orcid\Evaluation\OrcidIdEvaluator;
use UniPotsdam\Orcid\Hooks\SaveOrcidDataHook;

defined('TYPO3') or die();

// Register Extbase plugin for ORCID content element
ExtensionUtility::configurePlugin(
    'Orcid',
    'Works',
    [
        OrcidWorkController::class => 'list',
    ],
    [
        OrcidWorkController::class => '',
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

// Register hook to save orcid data in typo3 database after creating or updating the orcid content element
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['orcid_works']
    = SaveOrcidDataHook::class;

// Evaluator for orcid_id
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][OrcidIdEvaluator::class] = '';
