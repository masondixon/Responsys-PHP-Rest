<?php
/**
 * @author mdixon7@gmail.com
 * PHP class to interact with Responsys REST API
 * 2015-04-07
 */

class ResponsysRest
{
	// api call urls
	const login_service_url    = "/rest/api/v1/auth/token";
	const campaign_service_url = "/rest/api/v1/campaigns/";
	const list_service_url     = "/rest/api/v1/lists/";
	const event_service_url    = "/rest/api/v1/events/";
	const content_lib_item_url = "/rest/api/v1.1/clItems";
	
	// login urls
	const login_interact_2     = "https://login2.responsys.net";
	const login_interact_2a    = "https://login2a.responsys.net";
	const login_interact_5     = "https://login5.responsys.net";

	private $end_point    = null,
			$auth_token   = null,
			$debug        = false;
	
	public $serverChallenge = null,
		   $clientChallenge = null;
	
	public function __construct( $debug=false )
	{
		$this->debug = $debug;
	}
	

	/**
	 * @param unknown $login_url -> Use one of the constants login_interact_X where X is the pod number of your account )
	 * @param unknown $user_name -> the api user login
	 * @param unknown $password  -> password
	 * @return boolean
	 * @global auth_token
	 * @global end_point
	 */
	public function login( $login_url, $user_name, $password )
	{
		$login_response = false;
		
		$login_request_string = "user_name=$user_name&password=$password&auth_type=password";
		
		$curl_request = curl_init();
		
		$curlConfig = array(
							CURLOPT_URL            => $login_url . self::login_service_url,// . "?" . $login_request_string,
							CURLOPT_VERBOSE        => $this->debug,
							CURLOPT_HTTPHEADER     => array('Content-Type: application/x-www-form-urlencoded'),
							//CURLOPT_HTTPHEADER     => array('Content-Type: application/json'),
							CURLOPT_SSL_VERIFYPEER => false,
							CURLOPT_SSL_VERIFYHOST => false,
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_POST           => true,
							CURLOPT_POSTFIELDS     => $login_request_string,//'user_name=gha.api&password=Ymv3MOBTH2&auth_type=password',
							);
		
		curl_setopt_array( $curl_request, $curlConfig );
		
		$result = curl_exec( $curl_request );
		
		$http_return_code = curl_getinfo( $curl_request, CURLINFO_HTTP_CODE);
		
		$json_result = json_decode($result, true);
		
		if ( $http_return_code == 200 && ( curl_errno( $curl_request ) == 0 ) )
		{	
			//var_dump( $json_result);
			
			if( isset( $json_result['authToken'] ) && isset( $json_result['endPoint'] ) )
			{
				$this->auth_token = $json_result['authToken'];
				$this->end_point  = $json_result['endPoint'];
				$login_response = true;
			}
			else 
			{
				$login_response = false;
			}
		}
		else 
		{
			$this->print_debug( $curl_request, $json_result );
			$login_response = false;
		}
		
		curl_close( $curl_request );
		return $login_response;
	}
	
	public function base64SafeEncode( $data )
	{
		return rtrim( strtr( base64_encode( $data ), '+/', '-_'), '=');
	}
	
	public function base64SafeDecode( $data )
	{
		return base64_decode( strtr( $data, '-_', '+/') );
	}
	
	
	public function authenticate_server( $login_url, $user_name, $base64Safedchallenge )
	{
		$login_response = false;
		
		$login_request_string = "user_name=$user_name&client_challenge=$base64Safedchallenge&auth_type=server";
	
		$curl_request = curl_init();
	
		$curlConfig = array(
				CURLOPT_URL            => $login_url . self::login_service_url,
				CURLOPT_VERBOSE        => $this->debug,
				CURLOPT_HTTPHEADER     => array('Content-Type: application/x-www-form-urlencoded'),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => $login_request_string,
		);
	
		curl_setopt_array( $curl_request, $curlConfig );
	
		$result = curl_exec( $curl_request );
	
		$http_return_code = curl_getinfo( $curl_request, CURLINFO_HTTP_CODE);
	
		$json_result = json_decode($result, true);
	
		if ( $http_return_code == 200 && ( curl_errno( $curl_request ) == 0 ) )
		{
			//var_dump( $json_result);
				
			if( isset( $json_result['authToken'] ) && isset( $json_result['serverChallenge'] ) && isset( $json_result['clientChallenge'] ) ) 
			{
				$this->auth_token = $json_result['authToken'];
				$this->serverChallenge  = $json_result['serverChallenge'];
				$this->clientChallenge  = $json_result['clientChallenge'];
				
				$login_response   = true;
			}
			else
			{
				//print_r( $json_result );
				$login_response = false;
			}
		}
		else
		{
			$this->print_debug( $curl_request, $json_result );
			$login_response = false;
		}
	
		curl_close( $curl_request );
		return $login_response;
	}
	
	public function loginWithCertificate( $login_url, $user_name, $encrypted_server_challenge )
	{
		$login_response = false;
	
		$login_request_string = "user_name=$user_name&server_challenge=$encrypted_server_challenge&auth_type=client";
	
		$curl_request = curl_init();
		
		$headers = array( 'Content-Type: application/x-www-form-urlencoded',
				'Authorization: ' . $this->auth_token );
	
		$curlConfig = array(
				CURLOPT_URL            => $login_url . self::login_service_url,
				CURLOPT_VERBOSE        => $this->debug,
				CURLOPT_HTTPHEADER     => $headers,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => $login_request_string,
		);
	
		curl_setopt_array( $curl_request, $curlConfig );
	
		$result = curl_exec( $curl_request );
	
		$http_return_code = curl_getinfo( $curl_request, CURLINFO_HTTP_CODE);
	
		$json_result = json_decode($result, true);
	
		if ( $http_return_code == 200 && ( curl_errno( $curl_request ) == 0 ) )
		{
			//var_dump( $json_result);
	
			if( isset( $json_result['authToken'] ) && isset( $json_result['endPoint'] ) )
			{
				$this->auth_token = $json_result['authToken'];
				$this->end_point  = $json_result['endPoint'];
				$login_response = true;
			}
			else 
			{
				$login_response = false;
			}
		}
		else
		{
			$this->print_debug( $curl_request, $json_result );
			$login_response = false;
		}
	
		curl_close( $curl_request );
		return $login_response;
	}
	
	
	
	/**
	 * @param  String $folderName
	 * @param  String $listName
	 * @param  array $recordData -> see buildRecordDataArray
	 * @param  array $mergeRule  -> see buildListMergeRuleArray
	 * @return array $result
	 */
	public function manageList( $folderName, $listName, array $recordData, array $mergeRule )
	{	
		$result = array();
		
		if( isset( $this->auth_token ) && isset( $this->end_point ) )
		{
			$headers = array( 'Content-Type: application/json',
							  'Authorization: ' . $this->auth_token );
			
			$manageListParams = array();
			$manageListParams['list'] = array( 'folderName' => $folderName );
			$manageListParams['recordData'] = $recordData;
			$manageListParams['mergeRule']  = $mergeRule;
			
			$params = json_encode( $manageListParams );
			
			echo $params;
			
			$curl_request = curl_init();
			
			$curlConfig = array( CURLOPT_URL              => $this->end_point . self::list_service_url . $listName,
								 CURLOPT_VERBOSE          => $this->debug,
								 CURLOPT_HTTPHEADER       => $headers,
								 CURLOPT_RETURNTRANSFER   => true,
								 CURLOPT_POSTFIELDS       => $params );
			
			curl_setopt_array( $curl_request, $curlConfig );
			
			$curl_result = curl_exec( $curl_request );
			
			$http_return_code = curl_getinfo( $curl_request, CURLINFO_HTTP_CODE);
			
			$json_result = json_decode( $curl_result, true );
			
			if ( $http_return_code == 200 && ( curl_errno( $curl_request ) == 0 ) )
			{
				$result = $json_result;
			}
			else
			{
				$this->print_debug( $curl_request, $json_result );
			}
		}
		else
		{
			print ("You don't appear to be logged in, please login before attempting any operations.");
		}
		
		curl_close( $curl_request );
		return $result;
	}
	
	public function createContentLibraryItem( $content_lib_path, $file_name, $base64_binary_data )
	{
		$result = array();
		
		if( isset( $this->auth_token ) && isset( $this->end_point ) )
		{
			$headers = array( 'Content-Type: application/json',
					'Authorization: ' . $this->auth_token );
				
			$params = array();
			$params['itemPath'] = $content_lib_path . "/" . $file_name;
			$params['itemData'] = $base64_binary_data;
				
			$params = json_encode( $params );
				
			echo $params;
				
			$curl_request = curl_init();
				
			$curlConfig = array( CURLOPT_URL            => $this->end_point . self::content_lib_item_url,
								 CURLOPT_VERBOSE        => $this->debug,
								 CURLOPT_HTTPHEADER     => $headers,
								 CURLOPT_RETURNTRANSFER => true,
								 CURLOPT_POSTFIELDS     => $params );
				
			curl_setopt_array( $curl_request, $curlConfig );
				
			$curl_result = curl_exec( $curl_request );
				
			$http_return_code = curl_getinfo( $curl_request, CURLINFO_HTTP_CODE);
				
			$json_result = json_decode( $curl_result, true );
				
			if ( $http_return_code == 200 && ( curl_errno( $curl_request ) == 0 ) )
			{
				$result = $json_result;
			}
			else
			{
				$this->print_debug( $curl_request, $json_result );
			}
		}
		else
		{
			print ("You don't appear to be logged in, please login before attempting any operations.");
		}
		
		curl_close( $curl_request );
		return $result;
	}
	
	
	
	
	/*
	 * HELPER METHODS SECTIONS
	 * These functions are designed to help build specific parts of the call payloads.
	 */
	

	/**
	 * @param bool   $insertOnNoMatch   -> Inserts on no match if true.
	 * @param String $updateOnMatch     -> Takes one of two possible values : "REPLACE_ALL" , "NO_UPDATE".
	 * @param String $matchColumn1      -> MANDATORY : Can be any column in the contact list. Must be indexed!!! Typical values will be "EMAIL_ADDRESS_" or "CUSTOMER_ID_".
	 * @param String $matchColumn2      -> OPTIONAL  : Can be any column in the contact list. Must be indexed!!! Typical values will be "EMAIL_ADDRESS_" or "CUSTOMER_ID_".
	 * @param String $matchOperator     -> Takes one of two possible values : "NONE" , "AND" Use "NONE" if you only use one matchColumn.
	 * @param String $defaultPermission -> Takes one of two possible values : "OPTIN" , "OPTOUT". The initial state of the record when its first merged into the contact list. 
	 * @param String $rejectRecord      -> Takes CSV value of initials ( check API doc ). If you have "E", the merge will be rejected if the fieldList does not contain EMAIL_ADDRESS_ for example. 
	 * @return array $listMergeRule
	 */
	public function buildListMergeRuleArray( $insertOnNoMatch, $updateOnMatch, $matchColumn1, $matchColumn2, $matchOperator, $defaultPermission, $rejectRecord )
	{
		$listMergeRule = array();
		
		$listMergeRule['insertOnNoMatch']            = $insertOnNoMatch;
		$listMergeRule['updateOnMatch']              = $updateOnMatch;
		$listMergeRule['matchColumnName1']           = $matchColumn1;
		$listMergeRule['matchColumnName2']           = $matchColumn2;
		$listMergeRule['matchColumnName3']           = NULL;
		$listMergeRule['matchOperator']              = $matchOperator;
		$listMergeRule['defaultPermissionStatus']    = $defaultPermission;
		$listMergeRule['htmlValue']                  = 'H';
		$listMergeRule['textValue']                  = 'T';
		$listMergeRule['optinValue']                 = 'I';
		$listMergeRule['optoutValue']                = 'O';
		$listMergeRule['rejectRecordIfChannelEmpty'] = $rejectRecord;
		
		return $listMergeRule;
	}

	/**
	 * @param  array $fieldNames
	 * @param  array $fieldValuesArray
	 * @return array $recordData
	 */
	public function buildRecordDataArray( array $fieldNames, array $fieldValuesArray )
	{
		$recordData = array();
		
		$recordData['fieldNames'] = $fieldNames;
		$recordData['records']    = array();
		
		foreach( $fieldValuesArray as $fieldValues )
		{
			$recordData['records'][] = array('fieldValues' => $fieldValues);
		}
		
		//print_r( $recordData );
		return $recordData;
	}
	
	/**
	 * 
	 * @param array $multi_dimensional_data_array
	 * @return multitype:multitype:multitype:multitype:string   multitype:multitype:unknown
	 */
	public function buildTriggerDataArray( array $multi_dimensional_data_array )
	{
		$triggerDataArray = array();
		
		foreach ( $multi_dimensional_data_array as $recipient_array )
		{
			$optionalDataArray = array();
			
			foreach ( $recipient_array as $key => $name_value_array )
			{
				
				if( is_array( $name_value_array ) && count( $name_value_array ) > 0 )
				{
					
					foreach( $name_value_array as $name => $value )
					{
						$optionalData = array( 'optionalData' => array( 'name' => $name, 'value' => $value ) );
						$optionalDataArray[] = $optionalData;
					}
				
				}
				else
				{
					$optionalData = array( 'optionalData' => array( 'name' => "", 'value' => "" ) );
					$optionalDataArray[] = $optionalData;
				}
				
				$triggerDataArray[] = $optionalDataArray;			
			}
		}
		
		return $triggerDataArray;
		
	}
	
	/**
	 * @param unknown $curl_request
	 * @param unknown $json_result
	 */
	private function print_debug( $curl_request, $json_result )
	{
		if( $this->debug == true)
		{
			print ("*** There was a problem with the request ***\n");
			
			if( curl_errno($curl_request) != 0 )
			{
				print ( "CURL Error: " . curl_error( $curl_request ) );
				print ( "CURL Detail: " . curl_error( $curl_request ) );
			}
				
			print "Http Response Code : " . curl_getinfo( $curl_request, CURLINFO_HTTP_CODE) . "\n";
				
			foreach( $json_result as $key => $details )
			{
				if( isset( $details ) && $details != "" )
				{
					if( is_array( $details ) )
					{
						echo $key . " : "; print_r( $details );
						echo "\n";
					}
					else
					{
						echo $key . " : " . $details;
						echo "\n";
					}
				}
			}
		}
	}
	
// end rest class	
}

?>
