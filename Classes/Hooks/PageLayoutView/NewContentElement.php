<?php

namespace UniPotsdam\Orcid\Hooks\PageLayoutView;

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

use TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;


/**
 * Contains a preview rendering for the page module of CType="uporcidext"
 */
class NewContentElement implements PageLayoutViewDrawItemHookInterface
{

   /**
    * Preprocesses the preview rendering of a content element of type "My new content element"
    *
    * @param \TYPO3\CMS\Backend\View\PageLayoutView $parentObject Calling parent object
    * @param bool $drawItem Whether to draw the item using the default functionality
    * @param string $headerContent Header content
    * @param string $itemContent Item content
    * @param array $row Record row of tt_content
    *
    * @return void
    */
   public function preProcess(
      PageLayoutView &$parentObject,
      &$drawItem,
      &$headerContent,
      &$itemContent,
      array &$row
   )
   {
      if ($row['CType'] === 'uporcidext') {

        $title = LocalizationUtility::translate('LLL:EXT:orcid/Resources/Private/Language/locallang.xlf:up_orcid_extension_title', '');
        $idLabel = LocalizationUtility::translate('LLL:EXT:orcid/Resources/Private/Language/locallang_be.xlf:id.label', '');
        $citationlabel = LocalizationUtility::translate('LLL:EXT:orcid/Resources/Private/Language/locallang_be.xlf:citationstyle.label', '');

		$headerContent .= '<Strong>'.$title.'</Strong>';
		$itemContent .= '<p>'.$idLabel.': '.$row['orcid_id'] .'<br>';
		$itemContent .= $citationlabel.': '.$row['orcid_citation_style'] .'</p>';

		$drawItem = false;
      }
   }
}

