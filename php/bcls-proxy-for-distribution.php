<?php
/**
 * proxy for Brightcove RESTful APIs
 * gets an access token, makes the request, and returns the response
 * Accessing:
 *     (note you should **always** access the proxy via HTTPS)
 *     Method: POST
 *     request body (accessed via php://input) is a JSON object with the following properties
 *
 * {string} url - the URL for the API request
 * {string} [requestType=GET] - HTTP method for the request
 * {string} [requestBody] - JSON data to be sent with write requests
 * {string} [client_id] - OAuth2 client id with sufficient permissions for the request
 * {string} [client_secret] - OAuth2 client secret with sufficient permissions for the request
 *
 * Example:
 * {
 *    "url": "https://cms.api.brightcove.com/v1/accounts/57838016001/video",
 *    "requestType": "PATCH",
 *    "client_id": "0072bebf-0616-442c-84de-7215bb176061",
 *    "client_secret": "7M0vMete8vP_Dmb9oIRdUN1S5lrqTvgtVvdfsasd",
 *    "requestBody": "{\"description\":\"Updated video description\"}"
 * }
 *
 * if client_id and client_secret are not included in the request, default values will be used
 *
 * @returns {string} $response - JSON response received from the API
 */

// security checks
// if you want to do some basic security checks, such as checking the origin of the
// the request against some white list, this would be a good place to do it
// CORS enablement and other headers
header("Access-Control-Allow-Origin: *");
header("Content-type: application/json");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection");

// default account values
// if you work on one Brightcove account, put in the values below
// if you do not provide defaults, the client id, and client secret must
// be sent in the request body for each request
$default_client_id     = 'YOUR_CLIENT_ID';
$default_client_secret = 'YOUR_CLIENT_SECRET';

// get request body
$requestData = json_decode(file_get_contents('php://input'));

// set up access token request
// check to see if client id and secret were passed with the request
// and if so, use them instead of defaults
if (isset($requestData->client_id)) {
    $client_id = $requestData->client_id;
} else {
    $client_id = $default_client_id;
}

if (isset($requestData->client_secret)) {
    $client_secret = $requestData->client_secret;
} else {
    $client_secret = $default_client_secret;
}
$auth_string = "{$client_id}:{$client_secret}";

// make the request to get an access token
$request = "https://oauth.brightcove.com/v4/access_token?grant_type=client_credentials";
$curl          = curl_init($request);
curl_setopt($curl, CURLOPT_USERPWD, $auth_string);
curl_setopt($curl, CURLOPT_POST, TRUE);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
  'Content-type: application/x-www-form-urlencoded',
));

$response = curl_exec($curl);
$curl_info = curl_getinfo($curl);
$php_log = array(
  "php_error_info" => $curl_info
);
$curl_error = curl_error($curl);

curl_close($curl);

// Check for errors
// it's useful to log as much info as possible for debugging
if ($response === FALSE) {
  log_error($php_log, $curl_error);
}

// Decode the response and get access token
$responseData = json_decode($response, TRUE);
$access_token = $responseData["access_token"];
// get request type or default to GET
$method = "GET";
if ($requestData->requestType) {
    $method = $requestData->requestType;
}

// get the URL and authorization info from the form data
$request = $requestData->url;
// check for a request body sent with the request
if (isset($requestData->requestBody)) {
  $data = $requestData->requestBody;
}
  $curl = curl_init($request);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    'Content-type: application/json',
    "Authorization: Bearer {$access_token}"
  ));
  switch ($method)
    {
      case "POST":
        curl_setopt($curl, CURLOPT_POST, TRUE);
        if ($requestData->requestBody) {
          curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        break;
      case "PUT":
        // don't use CURLOPT_PUT; it is not reliable
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        if ($requestData->requestBody) {
          curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        break;
      case "PATCH":
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        if ($requestData->requestBody) {
          curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        break;
      case "DELETE":
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        if ($requestData->requestBody) {
          curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        break;
      default:
        // GET request, nothing to do;
    }
  $response = curl_exec($curl);
  $curl_info = curl_getinfo($curl);
  $php_log = array(
    "php_error_info" => $curl_info
  );
  $curl_error = curl_error($curl);
  curl_close($curl);

// Check for errors and log them if any
// note that logging will fail unless
// the file log.txt exists in the same
// directory as the proxy and is writable

if ($response === FALSE) {
  log_error($php_log, $curl_error);
}

function log_error($php_log, $curl_error) {
  $logEntry = "\nError:\n". "\n".date("Y-m-d H:i:s"). " UTC \n" .$curl_error. "\n".json_encode($php_log, JSON_PRETTY_PRINT);
  $logFileLocation = "log.txt";
  $fileHandle      = fopen($logFileLocation, 'a') or die("-1");
  fwrite($fileHandle, $logEntry);
  fclose($fileHandle);
  echo "Error: there was a problem with your API call"+
  die(json_encode($php_log, JSON_PRETTY_PRINT));
}

// return the response to the AJAX caller
echo $response;
?>
