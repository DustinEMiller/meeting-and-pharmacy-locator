<?php
	return [
		'sf.clientid' => get_cfg_var('salesforce.clientid'),
		'sf.clientsecret' => get_cfg_var('salesforce.clientsecret'),
		'sf.username' => get_cfg_var('salesforce.username'),
		'sf.password' => get_cfg_var('salesforce.pwd'),
		'sf.securitytoken' => get_cfg_var('salesforce.securitytoken'),
		'sf.loginurl' => 'https://test.salesforce.com/services/oauth2/token',//'https://login.salesforce.com/services/oauth2/token',
		'log' => 'log.txt',//'/var/log/sf/sf.log'
		'campaign.member.url' => '/services/data/v37.0/sobjects/CampaignMember',
		'lead.url' => '/services/data/v37.0/sobjects/Lead',
		'record.type' => "/services/data/v37.0/query?q=select+id+from+recordtype+where+sobjecttype+='lead'+and+name+=+'Medicare'",
		'seminar.report' => '/services/data/v37.0/analytics/reports/00OU0000003HwP3',
		'password.notification' => '/services/data/v37.0/analytics/reports/00O3B000000JmGi',
		'ses.sender' => get_cfg_var('ses.sender'),
		'ses.user' => get_cfg_var('ses.user'),
		'ses.password' => get_cfg_var('ses.password'),
	];
?>