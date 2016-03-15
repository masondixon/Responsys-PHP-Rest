<?php

/**
 * Login with Certificate process is a two step process for obtaining tokens via encryption/decryption handshake
 * The first pass is more of a unit test to verify that a string can be decrypted using the Responsys client certificate
 * and verified that the user is configured for using a certificate ( that to say that the user has had a certificate 
 * uploaded against it in the Responsys UI ).
 * 
 * The second pass then requires the user to encrypt the server challenge sent in the return of the first pass to 
 * negotiate the 2nd handshake to obtain the final auth token and end point for establishing calls past authentication.
 */

include('responsys_rest.php');

$config = parse_ini_file('config.ini', true);

$rest_api_instance = new ResponsysRest(true);

$client_challenge = "I am a test string";

$base64_safe_challenge = $rest_api_instance->base64SafeEncode( $client_challenge );

$login = $rest_api_instance->authenticate_server( $config['login_I5']['url'], $config['auth_I5']['login_cert'], $base64_safe_challenge );

$encrypted_client_challenge = $rest_api_instance->clientChallenge;

$b64safeDecoded = $rest_api_instance->base64SafeDecode( $encrypted_client_challenge );

// USE RESP CERT TO DECYRPT THE VALUE AND DIFF ON ORIGINAL STRING VALUE !!!
// GET A X509 INSTANCE, THEN GET THE PUBLIC KEY FOR openssl_public_decrypt

$responsys_certificate_file = file_get_contents("/Users/mdixon/Documents/certificatefun/ResponsysServerCertificate.cer");

$x509 = openssl_x509_read( $responsys_certificate_file );
openssl_x509_export($x509, $newX509 );
$pubKey = openssl_get_publickey( $newX509 );

if ( openssl_public_decrypt( $b64safeDecoded, $decrypted, $pubKey, OPENSSL_PKCS1_PADDING ) )
{
	//This is the handshake test made in the first pass - matching original string with decrypted string to verify
	if( $client_challenge === $decrypted )
	{
		echo "*** Inital handshake passed, proceeding to loginWithCertificate *** ";
		
		echo "*** Encrypting server challenge *** ";
		
		$base64_decoded_server_challenge = $rest_api_instance->base64SafeDecode( $rest_api_instance->serverChallenge );
		
		$masons_private = file_get_contents("/Users/mdixon/Documents/certificatefun/mdixon/php_privateKey.key");
		
		$privateKey = openssl_get_privatekey( $masons_private );
		
		if ( openssl_private_encrypt( $base64_decoded_server_challenge, $encrypted_server_challenge, $privateKey ) )
		{
			$base64_server_challenge = $rest_api_instance->base64SafeEncode( $encrypted_server_challenge);
			$logged_in = $rest_api_instance->loginWithCertificate( $config['login_I5']['url'], $config['auth_I5']['login_cert'], $base64_server_challenge );
			
			if( $logged_in )
			{
				echo "*** We have logged in with certificate *** ";
				// now the endpoint and authheader will be set in the private vars, usable for rest of methods.
			}
			
		}
		else
		{
			echo "*** Encryption of server challenge failed, exiting *** ";
		}		
	}
	else 
	{
		echo "*** Client challenge decrypted, but did not match original string, exiting ***";
	}
}
else
{
	echo openssl_error_string();
	die( "Failed to decrypt the client challenge - exiting" );
}


?>