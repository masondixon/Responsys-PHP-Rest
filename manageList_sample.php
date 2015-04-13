<?php

/**
 * manageList REST call will insert and / or update a record in a Responsys contact list
 */

include('responsys_rest.php');
$config = parse_ini_file('config.ini', true);
$rest_api = new ResponsysRest(true);

$login = $rest_api->login( $rest_api::login_interact_5, $config['auth_I5']['login'], $config['auth_I5']['pass'] );

if( $login )
{
	$fields = array("EMAIL_ADDRESS_", "CITY_");

	$record1 = array("mdixon@oracle.com", "San Bruno");
	$record2 = array("mdixon@gmail.com", "San Bruno");

	$fieldValuesArray[] = $record1;
	$fieldValuesArray[] = $record2;

	$recordData = $rest_api->buildRecordDataArray($fields, $fieldValuesArray);

	$mergeRule = $rest_api->buildListMergeRuleArray(true, "REPLACE_ALL", "EMAIL_ADDRESS_", NULL, "NONE", "OPTOUT", "E");

	$mergeResult = $rest_api->manageList('Mason', 'masonList1', $recordData, $mergeRule);

	print_r( $mergeResult );
}

?>