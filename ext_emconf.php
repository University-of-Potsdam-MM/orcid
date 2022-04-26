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

/**
 * Extension Manager/Repository config file for ext "orcid".
 */
$EM_CONF[$_EXTKEY] = [
    'title' => 'Orcid',
    'description' => 'Get the all data with typo3',
    'category' => 'plugin',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-10.4.99',
            'fluid_styled_content' => '9.5.0-10.4.99'
        ],
        'conflicts' => [
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'UniPotsdam\\Orcid\\' => 'Classes'
        ],
    ],
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'author' => 'Stefanie Lemcke',
    'author_email' => 'stefanie.lemcke@uni-potsdam.de',
    'author_company' => 'UniPotsdam',
    'version' => '1.0.0',
];

?>
