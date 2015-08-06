<?php
/*
 * manageList REST call will insert and / or update a record in a Responsys contact list
 * Call accepts up to 200 records per invocation, this is referred to as batching, and is preferable to spamming the system with 1 off requests
 */

try 
{

	include('responsys_rest.php');
	
	$config = parse_ini_file('config_default.ini', true);
	
	$debug = true;
	
	$rest_api = new ResponsysRest( $debug );
	
	$login = $rest_api->login( $config['login_url']['url'], $config['auth']['login'], $config['auth']['pass'] );
	
	if( $login )
	{
		// Columns value to reflect in DB ( email address is critical of course )
		$fields = array("EMAIL_ADDRESS_", "CITY_");
	
		$record1 = array("someemail@oracle.com", "San Bruno");
		$record2 = array("anotheremail@gmail.com", "San Bruno");
	
		$fieldValuesArray[] = $record1;
		$fieldValuesArray[] = $record2;
	
		$recordData  = $rest_api->buildRecordDataArray($fields, $fieldValuesArray);
	
		$mergeRule   = $rest_api->buildListMergeRuleArray(true, "REPLACE_ALL", "EMAIL_ADDRESS_", NULL, "NONE", "OPTOUT", "E");
	
		$mergeResult = $rest_api->manageList( 'Mason', 'masonList1', $recordData, $mergeRule );
	
		//print_r( $mergeResult );
		
		return $mergeResult;
	}

}
catch( Exception $err )
{
	var_dump( $err );
}

?>
