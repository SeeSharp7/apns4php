<?php
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

/*
* Author: René Fehlow
* Date: 26.09.2016
*/
$app = new \Slim\Slim();
$app->contentType('application/json');
$app->post('/apns/', function () use ($app) {
	if(defined('CURL_HTTP_VERSION_2_0') == false){
		echo json_encode("HTTP 2 is not supported!");
		return;
	}
	//Example call
	//http://127.0.0.1/apns?device_token={yourDeviceTokenHere}&apns_topic={yourApnsTopicHere}

	//Read url params
	$device_token 	= $app->request()->get('device_token');
	$apns_topic 	= $app->request()->get('apns_topic');
	$debugging 		= $app->request()->get('debugging'); //optional
	
	//Read body
	$payload		= $app->request->getBody();
	
	//Default values
	$url			= "https://api.push.apple.com/3/device/$device_token";
	$pem_file		= '/etc/ssl/certs/pushcert.pem';

	//Initialize curl
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("apns-topic: $apns_topic"));
	curl_setopt($ch, CURLOPT_SSLCERT, $pem_file);
	//curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $pem_secret); //only needed, when your .pem is password protected
	
	if ($debugging == "1") {
		$verbose = fopen('php://temp', 'w+');
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_STDERR, $verbose);
	}
	
	$response = curl_exec($ch);
	
	//Some logging here!
	if ($debugging == "1") {
		if ($response === FALSE) {
			printf("cUrl error (#%d): %s<br>\n", curl_errno($ch), htmlspecialchars(curl_error($ch)));
		}
		rewind($verbose);
		$verboseLog = stream_get_contents($verbose);
		echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
	}
	
	//Get status code
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
	//Wrap response code
	$app->response->setStatus($httpcode);
	
	//Return json (when status code is not 200, further information is provided by apple)
	echo $response;
});
$app->run();
