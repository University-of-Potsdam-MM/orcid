<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use UniPotsdam\Orcid\Evaluation\OrcidIdEvaluator;

defined('TYPO3') or die;

ExtensionUtility::registerPlugin(
    'Orcid',
    'Works',
    'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:orcid_works.CType',
    'orcid_icon',
    'default',
    'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:orcid_works.CType.description'
);

// Add configuration for the additional backend form fields
$temporaryColumns = [
    'orcid_citation_style' => [
        'exclude' => true,
        'label' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:citationstyle.label',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                [
                    'label' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:citationstyle',
                    'value' => '--div--',
                ],
                [
                    'label' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:citationstyle.acm-sig-proceedings',
                    'value' => 'acm-sig-proceedings',
                ],
                [
                    'label' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:citationstyle.american-chemical-society',
                    'value' => 'american-chemical-society',
                ],
                [
                    'label' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:citationstyle.apa',
                    'value' => 'apa',
                ],
                [
                    'label' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:citationstyle.associacao-brasileira-de-normas-tecnicas',
                    'value' => 'associacao-brasileira-de-normas-tecnicas'
                ],
                [
                    'label' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:citationstyle.chicago-author-date',
                    'value' => 'chicago-author-date',
                ],
                [
                    'label' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:citationstyle.harvard-cite-them-right',
                    'value' => 'harvard-cite-them-right',
                ],
                [
                    'label' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:citationstyle.ieee',
                    'value' => 'ieee',
                ],
                [
                    'label' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:citationstyle.modern-language-association',
                    'value' => 'modern-language-association'
                ],
                [
                    'label' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:citationstyle.royal-society-of-chemistry',
                    'value' => 'royal-society-of-chemistry'
                ],
            ],
            'size' => 1,
            'maxitems' => 1,
            'default' => '',
        ],
    ],
    'orcid_id' => [
        'exclude' => true,
        'label' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:orcid_id.label',
        'description' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:orcid_id.description',
        'config' => [
            'type' => 'input',
            'placeholder' => '0000-0000-0000-0000, 0000-0000-0000-0000',
            'size' => '30',
            'eval' => 'trim,' . OrcidIdEvaluator::class,
            'default' => '',
            'required' => true,
        ],
    ],
    'orcid_grouping_style' => [
        'exclude' => true,
        'label' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:orcid_grouping_style.label',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                [
                    'label' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:orcid_grouping_style.0',
                    'value' => 0,
                ],
                [
                    'label' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:orcid_grouping_style.1',
                    'value' => 1,
                ],
                [
                    'label' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:orcid_grouping_style.2',
                    'value' => 2,
                ],
            ],
            'size' => 1,
            'maxitems' => 1,
            'default' => 0,
        ],
    ],
    'orcid_profile_owner' => [
        'exclude' => true,
        'label' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:orcid_profile_owner.label',
        'description' => 'LLL:EXT:orcid/Resources/Private/Language/locallang_db.xlf:orcid_profile_owner.description',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 1,
        ],
    ],
];

ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    $temporaryColumns
);

// Add the configuration options for available fields for editors
$GLOBALS['TCA']['tt_content']['types']['orcid_works'] = [
    'showitem' => '
            --palette--;;header,
            orcid_profile_owner,
            orcid_id,
            orcid_citation_style,
            orcid_grouping_style,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
            --palette--;;appearanceLinks,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended
    ',
];
