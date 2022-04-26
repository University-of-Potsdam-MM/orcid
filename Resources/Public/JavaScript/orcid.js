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

/*
This file is used to generate the html depending on the publications
as well as the selected representation (citation style, grouping style, locale).

Also it contains some helper for calling the citeproc Engine. These functions were
copied from https://github.com/ORCID/orcid-js and adapted.The library contains more
functions that were not needed.
 */


/**
 * Create the page for the given citations.
 * @param divID id of the element to be able to distinguish between several elements
 * @param orcidData publications as json
 * @param groups array of available groups for grouping the orcidData. Can be an empty string, if groupingStyle == '0'
 * @param groupingStyle the groupingStyle (0 = no grouping, 1 = grouping by year, 2 = grouping by citation type)
 * @param citationStyle the csl citation style for rendering the citations
 * @param locales array of locales for rendering the citations, currently only german and englisch
 */
function renderData(divID, orcidData, groups, groupingStyle, citationStyle, locales) {

    if (orcidData) {
        let orcidDataElement = document.createElement("div");
        orcidDataElement.setAttribute("id", "orcid-work-data" + divID);
        orcidDataElement.setAttribute("class", "orcid_data");

        //add workdata
        let citeprocJSONObject = createCitationJSONObject(orcidData);

        let citeprocEngine = initCiteprocEngine(citeprocJSONObject, citationStyle, locales);

        if (groupingStyle != '0') {
            let skip = [];

            for (let i in groups) {
                let group = groups[i];
                /** If work types have been grouped together (same translation), they will be skipped**/
                if(skip.includes(i)){
                    continue;
                }

                //create new container for workdata of a year
                let groupElement = document.createElement("div");
                groupElement.setAttribute("class", "csl-bib-body");

                //add id and headings for groups
                groupElement.setAttribute("id", group + "-container" + divID);
                groupElement.insertAdjacentHTML('afterbegin', '<h3 class="cita-group">' + group + '</h3>');

                if (i != null && i.length > 0 && i != 'other') {
                    orcidDataElement.insertAdjacentElement('afterbegin', groupElement);
                } else {
                    orcidDataElement.insertAdjacentElement('beforeend', groupElement);
                }

                let filterList = [];
                if (groupingStyle == 1) {
                    filterList.push(createFilterItem("issued", group));
                } else if (groupingStyle == 2){
                    /** The translated work types are partly identical. Therefore all keys for each translation are searched and grouped if necessary.**/
                    let keys = Object.keys(groups).filter(k=>groups[k]===group);
                    for(let key in keys){
                        filterList.push(createFilterItem("type", keys[key]));
                        skip.push(keys[key]);
                    }

                }

                let filter = createFilter("include", filterList);
                let citations = createCitations(citeprocEngine, filter);

                for (let c in citations) {
                    groupElement.insertAdjacentHTML('beforeend', citations[c]);
                }

            }
        } else {
            //create new container for complete workdata
            let workDataElement = document.createElement("div");
            workDataElement.setAttribute("id", "container" + divID);
            workDataElement.setAttribute("class", "csl-bib-body");
            orcidDataElement.appendChild(workDataElement);

            let citations = createCitations(citeprocEngine);
            for (let c in citations) {
                workDataElement.insertAdjacentHTML('beforeend', citations[c]);
            }
        }
        return orcidDataElement;
    }

}

/**
 * Prepares the citation object for the citeproc engine.
 * (Copied from https://github.com/ORCID/orcid-js and adapted)
 * @param citations all citations as json
 * @returns {{citationItems: *[], properties: {noteIndex: number}}}
 */
function createCitationJSONObject(citations) {

    let id = 0;
    let citeprocJSONArray = [];

    for (let citation in citations) {
        citeprocJSONArray[id] = citations[citation];
        citeprocJSONArray[id].id = "" + id;
        //we have to remove nulls as they cause citeproc.js to fail.
        removeNullsInObject(citeprocJSONArray[id]);
        id++;
    }
    let citeprocJSONObject = {
        "citationItems": citeprocJSONArray,
        "properties": {
            "noteIndex": 0
        },
    }
    return citeprocJSONObject;
}

/**
 * Remove nulls in an object. Used the clean up the data for the citeproc engine.
 * with thanks to http://stackoverflow.com/questions/23774231/how-do-i-remove-all-null-and-empty-string-values-from-a-json-object
 * (Copied from https://github.com/ORCID/orcid-js and adapted)
 * @param obj
 */
function removeNullsInObject(obj) {
    if (typeof obj === 'string') {
        return;
    }
    $.each(obj, function (key, value) {
        if (value === "" || value === null) {
            delete obj[key];
        } else if ($.isArray(value)) {
            if (value.length === 0) {
                delete obj[key];
                return;
            }
            $.each(value, function (k, v) {
                removeNullsInObject(v);
            });
        } else if (typeof value === 'object') {
            if (Object.keys(value).length === 0) {
                delete obj[key];
                return;
            }
            removeNullsInObject(value);
        }
    });
}

/**
 * Initializes and returns the citeproc Engine
 * (Copied from https://github.com/ORCID/orcid-js and adapted)
 * @param citationsJSONObject created citation object as json (see createCitationJSONObject)
 * @param citationStyle citation style for rendering
 * @param locales array of locales
 * @param lang language to select the locale
 * @returns {CSL.Engine}
 */
function initCiteprocEngine(citationsJSONObject, citationStyle, locale) {

    let cslSys = {
        retrieveItem: function (id) {
            return citationsJSONObject.citationItems[id];
        },
        retrieveLocale: function (lang) {
            return locale;
        }
    }
    let citeprocEngine = new CSL.Engine(cslSys, citationStyle);
    citeprocEngine.appendCitationCluster(citationsJSONObject);
    return citeprocEngine;
}

/**
 * This function renders the citations using the citeproc engine
 * @param citeprocEngine the created engine (see initCiteprocEngine())
 * @param filter the filter for
 * @returns {*}
 */
function createCitations(citeprocEngine, filter = null){
    return citeprocEngine.makeBibliography(filter)[1];
}

/**
 * Creates and returns the filter with one or more items. It is used for grouping the citations (e.g. by year or by citation type).
 * See https://citeproc-js.readthedocs.io/en/latest/running.html#selective-output-with-makebibliography
 * @param keyword the name of the used filter (e.g "include" or "exclude")
 * @param  itemList the list of values to be filtered
 * @returns {Object}
 */
function createFilter(keyword, itemList) {
    let filter = new Object();
    filter[keyword] = itemList;
    return filter;
}

/**
 * Creates and returns a single filter item. It is used for grouping the citations (e.g. by year or by citation type).
 * See https://citeproc-js.readthedocs.io/en/latest/running.html#selective-output-with-makebibliography
 * @param field the name of the used group (e.g "type" for the citation types)
 * @param group the value to be filtered
 * @returns {{field, value}}
 */
function createFilterItem(field, value) {

    let item = {
        "field": field,
        "value": value
    }
    return item;
}