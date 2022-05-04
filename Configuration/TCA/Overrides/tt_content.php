<?php
defined('TYPO3_MODE') || die();
/**
 * --------------------------------------------------------------
 * This file is part of the package UniPotsdam\Orcid.
 * copyright 2020 by University Potsdam
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

call_user_func(function()
{

    /**
     * Temporary variables
     */
    $extensionKey = 'orcid';


    // Configure the default backend fields for the content element
    $GLOBALS['TCA']['tt_content']['types']['uporcidext'] = [
        'showitem' => '--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;
                        general,
                        orcid_profile_owner,
                        orcid_id,
					    orcid_citation_style,
					    orcid_grouping_style,
                      --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                        --palette--;;appearanceLinks,
                      --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                        --palette--;;language,
                      --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                        --palette--;;hidden,
                        --palette--;;access,
                      --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                        rowDescription,
                      --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,',
        'columnsOverrides' => [
            'bodytext' => [
                'config' => [
                    'enableRichtext' => true,
                    'richtextConfiguration' => 'default',
                ],
            ],
        ],
    ];



    // Created input fields for orcid plugin
    $temporaryColumns = array (
        'orcid_profile_owner' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:profileowner.label',
            'config' => [
                'type' => 'radio',
                'default' => 1,
                'items' => [
                    ['LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:profileowner.1', 1],
                    ['LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:profileowner.2', 0],
                ],
            ],
        ),
        'orcid_id' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:id.label',
            'config' => [
                'type' => 'input',
                'placeholder' => '0000-0000-0000-0000',
                'size' => '30',
                'eval' => 'trim,required',
            ],
        ),
        'orcid_citation_style' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:citationstyle.label',
            'config' => array (
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array (
                    array('LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:citationstyle', '--div--'),
                    array('LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:citationstyle.acm-sig-proceedings', 'acm-sig-proceedings'),
                    array('LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:citationstyle.american-chemical-society', 'american-chemical-society'),
                    array('LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:citationstyle.apa', 'apa'),
                    array('LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:citationstyle.associacao-brasileira-de-normas-tecnicas', 'associacao-brasileira-de-normas-tecnicas'),
                    array('LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:citationstyle.chicago-author-date', 'chicago-author-date'),
                    array('LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:citationstyle.harvard-cite-them-right', 'harvard-cite-them-right'),
                    array('LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:citationstyle.ieee', 'ieee'),
                    array('LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:citationstyle.modern-language-association', 'modern-language-association'),
                    array('LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:citationstyle.royal-society-of-chemistry', 'royal-society-of-chemistry'),
                    array('LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:citationstyle.turabian-fullnote-bibliography', 'turabian-fullnote-bibliography'),
                    array('LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:citationstyle.vancouver', 'vancouver'),
                ),
                'size' =>1,
                'maxitems' => 1,
            )
        ),
        'orcid_grouping_style' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:groupingstyle.label',
            'config' => [
                'type' => 'radio',
                'default' => 0,
                'items' => [
                    ['LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:groupingstyle.1', 0],
                    ['LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:groupingstyle.2', 1],
                    ['LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_be.xlf:groupingstyle.3', 2],
                ],
            ],
        ),
    );

    //add Orcid fields in tt_content table
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
        'tt_content',
        $temporaryColumns
    );

    // Adds the content element to the "Type" dropdown
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang.xlf:up_orcid_extension_title',
            'uporcidext',
            'orcid_icon',
        ],
        '--div--',
        'after'
    );


});
