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


#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content(
        orcid_citation_style varchar(255) DEFAULT '' NOT NULL,
        orcid_id varchar(255) DEFAULT '' NOT NULL,
        orcid_grouping_style int(1),
        orcid_profile_owner int(1),
);

#
# Table structure for table 'tx_orcid_maindata'
#
CREATE TABLE tx_orcid_maindata (
	uid int(11) NOT NULL auto_increment,

	orcid_id varchar(255) DEFAULT '' NOT NULL,
	author_name varchar(255) DEFAULT '' NOT NULL,
	
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
    UNIQUE KEY orcid_id(orcid_id)
);

#
# Table structure for table 'tx_orcid_workdata'
#
CREATE TABLE tx_orcid_workdata (
	uid int(11) NOT NULL auto_increment,
	
	orcid_workput_code varchar(255) DEFAULT '' NOT NULL,
	orcid_id varchar(255) DEFAULT '' NOT NULL,
	orcid_work_date varchar(255) DEFAULT '',
	orcid_work_data longtext DEFAULT '' NOT NULL,
	orcid_work_type varchar (255) DEFAULT '',

	orcid_last_modified bigint(20) unsigned DEFAULT '0' NOT NULL,

    tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	UNIQUE KEY orcid_workput_code(orcid_workput_code),
);
