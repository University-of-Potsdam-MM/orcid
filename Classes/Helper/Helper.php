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
  * Developer: Anuj Sharma (asharma@uni-potsdam.de)
  * Developer: Stefanie Lemcke (stefanie.lemcke@uni-potsdam.de)
  *
  * --------------------------------------------------------------
  */

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;


/**
* This class contains some helper functions for checking or preparing the data
*/
class Helper
{

    /**
     * Check for a valid format of the Ordid ID (0000-0000-0000-0000, last digit could be an X)
     * @param string $orcidID id to be checked
     * @return bool
     */
    public static function checkFormat(string $orcidID): bool
    {

        $regex = '/^[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{3}[X0-9]$/';

        if (preg_match($regex, $orcidID)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Function for the convert xml to json format according to the CSL PHP library
     * @param string $xmlString the orcid content as xml string
     * @return array array with informations (id of the entry, path, year) of all work data
     */
    public static function prepareWorkdata(string $data)
    {
        $json = json_decode($data);
        $result=array();

        //get authorname and alternative names for clean up of names in publications
        $authorNames = array();
        $author = Helper::getAuthor($data);
        array_push($authorNames, $author);
        foreach ($json->person->{'other-names'}->{'other-name'} as $otherName){
            $author = $otherName->content;
            array_push($authorNames, $author);
        }
        $result['authorNames'] = $authorNames;

        // prepare workdata information
        $workData = $json->{'activities-summary'}->works->group;
        foreach($workData as $workDataVal){
            $displayIndex = -1;
            $workSum = $workDataVal->{'work-summary'};
            foreach ($workSum as $workSumVal) {
                //only use the preferred record of an activity-group. the preferred version has the highest display index
                if(($displayIndex < intval($workSumVal->{'display-index'}))){
                    $work = array();
                    $work['id']              = strval($workSumVal->{'put-code'});
                    $work['path']            = strval($workSumVal->{'path'});
                    $work['year']            = strval($workSumVal->{'publication-date'}->year->value);
                    $work['lastModified']    = intval($workSumVal->{'last-modified-date'}->value);
                    $displayIndex = intval($workSumVal->{'display-index'});
                }
                $result[$work['id']] = $work;
            }
        }
        return $result;
    }


    /**
     * Gets author Name from XMl content
     * @param string $result
     *
     */
    public static function getAuthor($data){

        $json = json_decode($data);
        $person = $json->person;
        //Given Name
        $f_name = strval($person->name->{'given-names'}->value);
        //Family Name
        $l_name = strval($person->name->{'family-name'}->value);
        $authorName = $f_name.' '.$l_name;
        return $authorName;
    }


    /**
     * This function cleans up the data from unusable entry (e.g. wrong http status codes) and prepares author data
     * @param $workData array with the work data
     * @return array enriched work data array
     */
    public static function enrichWorkData($workData, $alternativeAuthorNames){
        $result = array();
        foreach ($workData as $key => $value) {
            $data =  json_decode($value['data']);
            $check = self::cleanupWorkdata($data);
            if($check) {
                //get workType
                $value['type'] = $data -> type;
                $data = self::checkAuthorData($data, $alternativeAuthorNames);
                $value['data'] = json_encode($data);
                $result[$key] = $value;
            }
        }
        return $result;

    }


    /**
     * This function checks whether the entry could be retrieved
     * @param $entry workdata entry
     * @return true|false
     */
    private static function cleanupWorkdata($entry){

        if (!empty($entry) && !property_exists($entry, 'responseCode') && !property_exists($entry, 'response-code')) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * This function checks if the author data have the correct form
     * @param $entry the publication data as decoded json
     * @param $alternativeAuthorNames all (alternative) names of the profile owner for cleaning up the authors in the publication
     * @return array the publication data with cleaned autor data (as decoded json)
     */
    private static function checkAuthorData($entry, $alternativeAuthorNames){

        $authors = $entry->author;
        // author-entry contains a literal-key -> must be converted to family and given
        foreach($authors ?? [] as $value){
            if(isset($value->literal)){
                //split all names by 'and'
                $authorSplit =  explode(" and ", $value->literal);
                //trim whitespaces
                $authorSplit = array_map('trim', $authorSplit);
                $newAuthors = array();
                foreach($authorSplit as $author){
                    $newAuthor = self::formatAuthor($author);
                    //name equals the first entry -> remove first entry, because it is duplicated
                    $newAuthorTemp = $newAuthor->given.' '.$newAuthor->family;
                    $firstAuthorTemp = $newAuthors[0]->given.' '.$newAuthors[0]->family;
                    //first entry and current entry are both in the alternativeAutorNames array -> the author is duplicated and first entry ill be removed
                    // in case, only the initial letter of the given name is given, check lastname and first letter of givenname ot the both names
                    if((in_array($newAuthorTemp, $alternativeAuthorNames) && in_array($firstAuthorTemp, $alternativeAuthorNames)) ||
                        ($newAuthor->family === $newAuthors[0]->family && $newAuthor->given[0] === $newAuthors[0]->given[0])) {
                            array_shift($newAuthors);

                    }
                    array_push($newAuthors, $newAuthor);
                }

                $entry->author = $newAuthors;
            } else if(isset($value->family) && (ctype_upper($value->family) || preg_match("/[A-Z]+\./", $value->family))){
                //Last name contains only capital letters -> could be the initial letters and last name and first name are reversed
                $temp = $value->family;
                $value->family = $value->given;
                $value->given = $temp;
            }
        }
        return $entry;
    }


    /**
     * This function converts author data given as string into array with family and given key
     * @param $author the author data as string
     * @return array the splited author data
     */
    private static function formatAuthor($author){
        //list of name extensions to recognize the last name
        $extensions= array("von", "zu", "vom", "zum", "di", "de", "del", "da", "degli", "dalla", "van", "ter");
        //split the name by the last position of whitespace
        $authorNameSplit = preg_split("/\s(?=[^\s]*$)/", $author);
        if(preg_match('/,/', $author)){
            //names are in form 'familyname, givennames' and must be split by ', '
            $authorNameSplit=  explode(", ", $author, 2); // only the first occurence??
            //build new Array with keys family and given
            $newAuthor = new \stdClass();
            $newAuthor->family = $authorNameSplit[0];
            $newAuthor->given = $authorNameSplit[1];
        } else if( ctype_upper($authorNameSplit[1])) {
            //the last part of the name consists only of upper cases -> could be the first name
            $newAuthor = new \stdClass();
            $newAuthor->family = $authorNameSplit[0];
            $newAuthor ->given = $authorNameSplit[1];
        } else if (!empty($result = array_intersect(array_map('strtolower', explode(" ", $author)), $extensions))) {
            //name contains an extension
            $newAuthor = new \stdClass();
            $newAuthor->family = stristr($author, $result[key($result)]);
            $newAuthor->given = stristr($author, $result[key($result)], true);
        } else {
            //names are in form 'givenname familynames' and must be split by ' '
            $newAuthor = new \stdClass();
            $newAuthor->family = $authorNameSplit[1];
            $newAuthor ->given = $authorNameSplit[0];
        };
        return $newAuthor;

    }

    /**
     * Creates a error message and adds it to the message queue
     * @param string $messageTitle the title of the message
     * @param string $messageText details og the massage
     */
    public static function errorMessage(string $messageTitle, string $messageText)
    {
        $message = GeneralUtility::makeInstance(
            FlashMessage::class,
            $messageText,
            $messageTitle,
            FlashMessage::ERROR
        );

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $flashMessageService = $objectManager->get(FlashMessageService::class);
        $messageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $messageQueue->addMessage($message);
    }

    /**
     * Creates a success message and adds it to the message queue
     * @param string $messageTitle the title of the message
     * @param string $messageText details og the massage
     */
    public static function successMessage($messageTitle, $messageText)
    {
        $message = GeneralUtility::makeInstance(
            FlashMessage::class,
            $messageText,
            $messageTitle,
            FlashMessage::OK
        );

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $flashMessageService = $objectManager->get(FlashMessageService::class);
        $messageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $messageQueue->addMessage($message);
    }

}