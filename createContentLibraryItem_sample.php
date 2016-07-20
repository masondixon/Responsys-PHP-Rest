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
	
	$login = $rest_api->login( $config['login_url']['url'], $config['auth_content_library']['login'], $config['auth_content_library']['pass'] );

	if( $login )
	{
		// We need an actual valid location in the content library
		$content_location = '/contentlibrary/1_mason';
		$file_name        = 'random_file_mason.jpg';
		$image_data       = base64_encode( file_get_contents( 'pic1.jpg' ) );	
		$result           = $rest_api->createContentLibraryItem( $content_location, $file_name, $image_data );
		//print_r( $result );
		return $result;
	}
	else
	{
		die( "Login failed - exitting" );
	}
	

}
catch( Exception $err )
{
	echo "----- Exception -----";
	var_dump( $err );
	die("----- DYING -----");
}

?>
