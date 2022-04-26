/*
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
define(['jquery'], function($) {

    $(document).ready(function() {

		$('button.btn-sm').click(function() {
            var name;
		    name = $('.typo3-TCEforms .tab-content').find('select').val();
            // var n = name.includes("[CType]");
            if (name == 'uporcidext'){
                alert(
                    'Verarbeitung der ORCID-Daten! Dies kann bei großen Datenmengen einige Minuten dauern. ' +
                    'Bitte aktualisieren Sie diese Seite nicht. Sie werden informiert, wenn die Verarbeitung der Daten abgeschlossen ist. \n\n' +
                    'Processing the ORCID data! This can take a few minutes for large amounts of data. ' +
                    'Please don\'t refresh this page. You will be informed when the processing of the data is finished.');
                $( ".t3js-module-body" ).prepend( "<div id=\"orcidbar\"></div>" );
            }

		});

    });
});