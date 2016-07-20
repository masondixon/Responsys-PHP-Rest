<?php
/*
 * createContentLibraryItem allows developer to upload binary content to an existing content library folder
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
	die( "----- Exiting -----" );
}

?>
