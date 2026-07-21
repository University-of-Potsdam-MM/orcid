<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'orcid_icon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:orcid/Resources/Public/Icons/Backend/orcid_icon.svg',
    ],
];
