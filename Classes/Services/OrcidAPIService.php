<?php
namespace UniPotsdam\Orcid\Services;

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

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use UniPotsdam\Orcid\Helper\ErrorCodes;

/**
 * This class contains the functions for the orcid api access
 */
class OrcidAPIService
{

    /**
     * Retrieves the Orcid Data for a given orcid ID
     * @param string $orcidID the id of the desired data set
     * @return bool|array the data set as array (orcid => content) or false, if no data set is found
     */
    public static function getOrcidData(string $orcidID){
        $headers = array(
            'Accept: application/json'
        );
        $path = "/".$orcidID;
        $content = self::callAPI($path, $headers);

        if ($content instanceof ErrorCodes) {
            return $content;
        } else {
            return array($orcidID => $content);
        }
    }

    /**
     * Retrieves the orcid data for a set of orcid IDs
     * @param array $orcidIDs the ids as array of the desired data set
     * @return array the data sets as array (orcid => content)
     */
    public static function getAllOrcidData(array $orcidIDs){
        //Curl to get data from Orcid Api

        $headers = array(
            'Accept: application/json'
        );

        $result = array();
        foreach ($orcidIDs as $value) {

            $path = "/".$value["orcid_id"];
            $content = self::callAPI($path, $headers);
            if(!$content instanceof ErrorCodes){
                $result[$value["orcid_id"]] = $content;
            }
        }
        return $result;

    }

    /**
     * Retrieves the detailed work data for a set of work data (id, path, year) and adds the work data
     * @param array $workData set of work data information (id, path, year)
     * @param array $storedWorkData array of stored data (id, tstamp) to check if entry is up to date or not
     * @return array set of work data information (id, path, year, (bibtex) data )
     */
    public static function getOrcidWorkData(array $workData, array $storedWorkData, $ignoreTimestamps = false) {

        $headers = array(
            'Accept: application/vnd.citationstyles.csl+json'
        );
        $result = array();
        //single api calls, because multi_curl ist to fast for api (24 requests per second)
        foreach ($workData as $value) {
            //api is only called if the dataset is not up to date
            if(array_key_exists('path', $value) && ($ignoreTimestamps || $value['lastModified'] > $storedWorkData[$value['id']])){
                $content = self::callAPI($value['path'], $headers);
                if(!$content instanceof ErrorCodes){
                    $value['data'] = $content;
                    $result[$value['id']] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * This function encapsulates the API call.
     * Due to the restrictions of the API, multi curl is not used.
     * @param string $path The specific path of the resource
     * @param array $headers Optional headers
     * @return array|ErrorCodes the response of the api call
     */
    public static function callAPI(string $path, array $headers = null) {

        $configuration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('orcid');
        $apiUrl = trim($configuration['apiurl']);
        $proxy = trim($configuration['proxy']);

        $logger = GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);

        if(!is_null($apiUrl) && !empty($apiUrl)) {

            $curlHandler = curl_init();

            //set curl options
            curl_setopt($curlHandler, CURLOPT_URL, $apiUrl . $path);
            if (!is_null($headers)) {
                curl_setopt($curlHandler, CURLOPT_HTTPHEADER, $headers);
            }
            if (!is_null($proxy) && !empty($proxy)) {
                curl_setopt($curlHandler, CURLOPT_PROXY, $proxy);
            };
            curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curlHandler, CURLOPT_TIMEOUT, 60); //60s

            // Execute
            $content = curl_exec($curlHandler);
            $httpCode = curl_getinfo($curlHandler, CURLINFO_RESPONSE_CODE);
            $curlError = curl_errno($curlHandler);

            curl_close($curlHandler);
            // Check if any error occurred
            if ($curlError) {
                $logger->error('Curl error: ' . $curlError);
                return ErrorCodes::cast(ErrorCodes::CURL_ERROR);
            }


            if ($httpCode != 200 && !empty($content)) {
                $logger->warning('Wrong HTTP code: '. $httpCode);
                return ErrorCodes::cast(ErrorCodes::WRONG_HTTP_CODE);
            } else {
                return $content;
            }

        } else {
            $logger->warning('ORCiD API Url is missing!');
            return ErrorCodes::cast(ErrorCodes::MISSING_API_URL);
        }

    }


}