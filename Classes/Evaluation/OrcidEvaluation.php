<?php
namespace UniPotsdam\Orcid\Evaluation;

 /**
  * -------------------------------------------------------------
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

 use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
 use UniPotsdam\Orcid\Helper\Helper;


 class OrcidEvaluation{


     /**
      * JavaScript code for client side validation/evaluation
      * CHeck the correct format of the given orcid id and retrieve the data from the API, if no database entry was found.
      *
      * @param string $value The field value to be evaluated, in this case the orcid ID
      * @param string $is_in The "is_in" value of the field configuration from TCA
      * @param bool $set Boolean defining if the value is written to the database or not.
      * @return string Evaluated field value
      */
    public function evaluateFieldValue(string $value, string $is_in, bool &$set)
    {

        $value = trim($value);
        if(is_null($value) || !Helper::checkFormat($value)){
            $error = LocalizationUtility::translate('LLL:EXT:orcid/Resources/Private/Language/locallang_be.xlf:formatError');
            $hint = LocalizationUtility::translate('LLL:EXT:orcid/Resources/Private/Language/locallang_be.xlf:formatError.hint');
            Helper::errorMessage($error, $hint);
            $set = false; //do not save value
        }
        return $value;        
    }

    /**
     * Server-side validation/evaluation on opening the record
     *
     * @param array $parameters Array with key 'value' containing the field value from the database
     * @return string Evaluated field value
     */
    public function deevaluateFieldValue(array $parameters) 
    {
        return $parameters['value'];
    }


 }

