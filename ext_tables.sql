# Defining tables without TCA
#
# Table structure for table 'tx_orcid_maindata'
#
CREATE TABLE tx_orcid_maindata (
    uid int(11) NOT NULL auto_increment,

    orcid_id varchar(255) DEFAULT '' NOT NULL,
    given_name varchar(255) DEFAULT '' NOT NULL,
    family_name varchar(255) DEFAULT '' NOT NULL,

    last_sync_at int(11) unsigned DEFAULT '0' NOT NULL,
    last_sync_status varchar(32) DEFAULT '' NOT NULL,
    last_sync_message text,

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

    orcid_id varchar(255) DEFAULT '' NOT NULL,
    put_code int(11) unsigned DEFAULT '0' NOT NULL,
    work_date varchar(255) DEFAULT '',
    work_month int(11) unsigned DEFAULT '0' NOT NULL,
    raw_data longtext DEFAULT '' NOT NULL,
    title text,
    work_type varchar (255) DEFAULT '',
    doi varchar(255) DEFAULT '' NOT NULL,
    doi_url varchar(255) DEFAULT '' NOT NULL,
    work_url varchar(255) DEFAULT '' NOT NULL,
    journal_title text,
    authors longtext,
    last_modified bigint(20) unsigned DEFAULT '0' NOT NULL,
    citation_type varchar(50) DEFAULT '' NOT NULL,
    citation_value longtext,

    tstamp int(11) unsigned DEFAULT '0' NOT NULL,
    crdate int(11) unsigned DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    UNIQUE KEY put_code (orcid_id, put_code),
    KEY orcid_year (orcid_id, work_date)
);
