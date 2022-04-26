<?php
namespace UniPotsdam\Orcid\ViewHelpers;
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

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use UniPotsdam\Orcid\Services\Database\MainDataDatabaseService;
use UniPotsdam\Orcid\Services\Database\WorkDataDatabaseService;

class OrciddataViewHelper extends AbstractViewHelper {


    //initialize attribute for viewhelper tag of html template
    public function initializeArguments()
    {
        $this->registerArgument('uid', 'string', 'uid use to get orcid id', true);
        $this->registerArgument('orcidstyle', 'string', 'Apply Citation Style on Orcid data', true);
        $this->registerArgument('groupingStyle', 'string', 'Sorting', true);
    }



    //Render Function for Orcid Data
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    )
    {
        $uid = $arguments['uid'];
        $orcidstyle = $arguments['orcidstyle'];
        $groupingStyle = $arguments['groupingStyle'];

        $authorName = trim(MainDataDatabaseService::getAuthorName($uid));
        $groups = null;
        $styleContent = null;
        $localesContent = null;
        $workarray[$uid] = null;

        //if no author name is found, there is also no data set
        if (!empty($authorName)) {

            // citation styles file
            $styleContentPath = GeneralUtility::getFileAbsFileName('EXT:orcid/extResources/citation-style-language/styles/' . $orcidstyle . '.csl');
            $styleContent = (is_file($styleContentPath)) ? file_get_contents($styleContentPath) : '';

            //locales files
            $context = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class);
            $langId = $context->getPropertyFromAspect('language', 'id');
            if($langId == 2){
                //german
                $localesContentPath = GeneralUtility::getFileAbsFileName('EXT:orcid/extResources/citation-style-language/locales/locales-de-DE.xml');
            } else {
                //english
                $localesContentPath = GeneralUtility::getFileAbsFileName('EXT:orcid/extResources/citation-style-language/locales/locales-en-GB.xml');
            }
            $localesContent = (is_file($localesContentPath)) ? file_get_contents($localesContentPath) : '';

            $workarray[$uid] = WorkDataDatabaseService::getWorkData($uid);


            if ($groupingStyle == 1) {
                $groups = WorkDataDatabaseService::getWorkYears($uid);
            } elseif ($groupingStyle == 2) {
                $worktypes = WorkDataDatabaseService::getWorkTypes($uid);
                // translate worktypes
                if(!is_null($worktypes)){
                    $groups = array();
                    foreach ($worktypes  as $type){
                        if(!empty($type)) {
                            $translation = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($type, "orcid");

                        } else {
                            $translation = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate("other", "orcid");
                        }
                        $groups[$type] = empty($translation) ? $type : $translation;
                    }
                }
            }
        }

        return self::prepareDataForRendering($workarray, $authorName, $styleContent, $localesContent, $groups);

    }


    /**
     * This function prepare the data for the rendering by citeproc.js
     * The author data containing a literal key, will be converted and the special characters will be escaped
     * @param $workdata the work data
     * @param $author
     * @param $style the citation style where special characters must be escaped
     * @param $locales the lacales where special characters must be escaped
     * @return array
     */
    public static function prepareDataForRendering($workdata,  $author, $style, $locales, $groups = null){
        $csltext = array();

        //prepare workdata
        foreach($workdata as $data){
            $data = json_encode($data);
            $data= preg_replace('/\W/', '\\\\$0', $data);
            $data = strval($data);

            $style= str_replace(array("\r", "\n"), '', $style);
            $style = addslashes($style);

            $locales = str_replace(array("\r", "\n"), '', $locales);
            $locales = addslashes($locales);

            $auth_name = $author;
            $csltextarr=[];
            $csltextarr['author']= $auth_name;
            $csltextarr['style']= strval($style);
            $csltextarr['locales']= strval($locales);
            $csltextarr['jsonData']= $data;

            if(!is_null($groups)){
                $groups = json_encode($groups);
                $csltextarr['groups']= strval($groups);
            }

            array_push($csltext, $csltextarr);

        }

        return $csltext;

    }

}
