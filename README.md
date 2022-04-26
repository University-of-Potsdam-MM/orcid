# ORCiD TYPO3-Extension
[![CC BY-SA 4.0][cc-by-sa-shield]][cc-by-sa]

This Typo3-Extension allows researchers to integrate their publications from ORCID into a Typo3 website. The extensions takes an ORCID ID, queries the ORCID API for publications and displays the publications in the frontend. Citation style and grouping options can be selected in the backend. The extension updates the list of publications on save and once a day via cronjob.

## Versions

<table>
<thead>
<tr>
<th>Oricd Ext</th>
<th>TYPO3</th>
<th>PHP</th>
</tr>
</thead>
<tbody>
<tr>
<td>1.x</td>
<td>9.0 - 9.x </td>
<td>7.0 - 7.x</td>
</tr>
</tbody>
</table>

### Version 1
Personal publication lists from ORCID can be displayed in Typo3.


## Project structure
(see https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/ContentElements/AddingYourOwnContentElements.html)

```
    Classes
        Evaluation (Field evaluation: Verification of the format of the ORCID ID)
        Helper	(Help functions and error codes)
        Hooks (Hook for saving the orcid data and Hook for new content element)
        Services (Implementation of database access and Orcid API access)
        Task  (Automated tasks for updating and deleting existing ORCiD data)
        ViewHelpers (ViewHelper for displaying the data in the frontend. First preprocessing of the data)
    Configuration
    extResources(Copy of CSL files for citation styles and languages -> see remarks.)
    Resources
        Private
            Language (Language files for front and back end)
            Templates (HTML template for frontend)
        Public
            Css 
            Icons
            Javascript (Javascript library (https://github.com/Juris-M/citeproc-js) for rendering the publications and some helper functions for using the library)
```



## Installation and Configuration

### Prerequisites
curl
php-curl

### Installation
1. Copy project to `<typo3 path>/typo3conf/ext/orcid`.
2. Delete backend and frontend caches.
3. Activate extension under *Extensions*.
4. Add ORCID Data (orcid) Template under *Template > Page-Template > Edit the whole template record > Includes*.
5. Set API URL and (if necessary) Proxy under *Settings > Extension Configuration > orcid* 
    (ORCID API Url: https://pub.orcid.org/v3.0)
6. Activate Tasks and CronJob (see below)

### Tasks
There are two tasks to keep the Orcid data up to date:
- Update Orcid Data: Updates all Orcid profiles contained on a Typo3 site that have a recent last-modified-date. 
	- Important fields
		- Frequency (seconds or cron command): e.g. * 3 * * * (3 o'clock at night)
		- Update all data (timestamp is ignored): if checked, **all** publication data will be updated. Timestamps are ignored. This function can be used if the code for preparing the data has changed.
-  Delete Orcid Data:  Deletes all Orcid profiles (and their publications) that are not included on any page.
	-  Important fields
		-  Frequency (seconds or cron command): e.g. * 3 * * * (3 o'clock at night)

To execute the scheduler tasks automatically, a cron job must be set in the system (see https://docs.typo3.org/c/typo3/cms-scheduler/main/en-us/Installation/CronJob/Index.html).

### Logs
The class OrcidAPIService uses a logger. The log file can be found here `[typo3-path]/log/orcid_error.log`.

The configuration (e.g. path) of the logger can be changed in ext_localconf.php.

### Work types
The publication types are translated in the language files for the frontend (see  `Resources/Private/Language/(de.)locallang.xlf`). Because the translations are done server-side, the publication types are translated with the help of the `ViewHelpers/OrciddataViewHelper.php`. 

Some publication types have the same translation. In this case they are combined (see `Resources/Public/JavaScript/orcid.js`).

### Citation Style Language - styles and locales 
The CSL files (see section [Citation Style Language - styles and locales](#citation-style-language-styles-and-locales)) are stored unchanged as a copy under `extRessources`. The integration via Composer to ensure automatic updates did not work, because the dependencies of the plugins are not resolved when updating Typo3 for performance reasons.

Currently only a selection of citation styles is offered. It can be extended in `Configuration/TCA/Overrides/tt_content.php`.

## ORCiD API
Currently the ORCiD API version 3 is used: https://pub.orcid.org/v3.0
Used Functions:
- `https://pub.orcid.org/v3.0/[orcid-id]`
	Summary of the data of a person for the specified ORCiD ID
- `https://pub.orcid.org/v3.0/[orcid-id]/work/[work-put-code]`
	Data of a concrete research work
	The header is set to `'Accept: application/vnd.citationstyles.csl+json'`, to get the data in the citeproc-json format needed for the csl renderer (citeproc-js)
	
For more information about the API, see https://info.orcid.org/documentation/api-tutorials/api-tutorial-read-data-on-a-record/

## Used projects

### Citation Style Language - styles and locales 
The used CSL-files ([styles](https://github.com/citation-style-language/styles) and [locales](https://github.com/citation-style-language/locales)) were developed and released by the [CSL project](https://citationstyles.org/) and licensed under [CC BY-SA](https://creativecommons.org/licenses/by-sa/3.0/)

URL: https://citationstyles.org/

In this project the CSL files are used unchanged.

### citeproc-js
[citeproc-js](https://github.com/Juris-M/citeproc-js) implements the Citation Style Language. It is developed by &copy; Frank Bennett. 

URL: https://citationstyles.org/

A bug in the filter function was fixed. The bug fix has been reported, but as far as we know it has not been adopted yet.

For more information about citeproc-js, see [citeproc-js manual](https://citeproc-js.readthedocs.io/en/latest/)

### orcid-js
Some functions to prepare the data for the csl-renderer (citeproc-js) were copied from https://github.com/ORCID/orcid-js and adapted. The relevant functions are marked in the code (see `Resources/Public/JavaScript/orcid.js`).


## Licensing
[![CC BY-SA 4.0][cc-by-sa-image]][cc-by-sa]

ORCID-TYPO3 displays scientific publications in TYPO3. It was developed by the University of Potsdam. 

URL: https://github.com/University-of-Potsdam-MM/orcid

This project is licensed under a [Creative Commons Attribution-ShareAlike 4.0 International License][cc-by-sa].


[cc-by-sa]: http://creativecommons.org/licenses/by-sa/4.0/
[cc-by-sa-image]: https://licensebuttons.net/l/by-sa/4.0/88x31.png
[cc-by-sa-shield]: https://img.shields.io/badge/License-CC%20BY--SA%204.0-lightgrey.svg