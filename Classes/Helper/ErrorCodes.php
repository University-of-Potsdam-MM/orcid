<?php

namespace UniPotsdam\Orcid\Helper;

 /**
  * -------------------------------------------------------------
  * This file is part of the package UniPotsdam\Orcid.
  * copyright 2022 by University Potsdam
  * https://www.uni-potsdam.de/
  *
  * GitHub repo: https://github.com/University-of-Potsdam-MM/orcid
  *
  * Project: Orcid Extension
  * Developer: Stefanie Lemcke (stefanie.lemcke@uni-potsdam.de)
  *
  * --------------------------------------------------------------
  */

/**
* Error codes
*/
final class ErrorCodes extends \TYPO3\CMS\Core\Type\Enumeration
{
    const MISSING_API_URL = 0;
    const WRONG_HTTP_CODE = 1;
    const CURL_ERROR = 2;

}