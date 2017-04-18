<?php

/**
 * ApiClient
 *
 * PHP version 5
 *
 * @category Services
 * @package  Authy
 * @author   David Cuadrado <david@authy.com>
 * @license  http://creativecommons.org/licenses/MIT/ MIT
 * @link     http://authy.github.com/pear
 */
/**
 * Authy API interface.
 *
 * @category Services
 * @package  Authy
 * @author   David Cuadrado <david@authy.com>
 * @license  http://creativecommons.org/licenses/MIT/ MIT
 * @link     http://authy.github.com/pear
 */

namespace Authy;

define('MIN_TOKEN_SIZE',6);
define('MAX_TOKEN_SIZE',12);
define('MAX_STRING_SIZE',200);

class AuthyApi {

    const VERSION = '2.5.0';

    protected $rest;
    protected $api_key;
    protected $api_url;

    /**
     * Constructor.
     *
     * @param string $api_key Api Key
     * @param string $api_url Optional api url
     */
    public function __construct($api_key, $api_url = "https://api.authy.com") {
        $this->rest = new \GuzzleHttp\Client(array(
            'base_uri' => "{$api_url}/protected/json/",
            'headers' => array('User-Agent' => $this->__getUserAgent(), 'X-Authy-API-Key' => $api_key),
            'http_errors' => false,
            'debug' => false
        ));

        $this->api_key = $api_key;
        $this->api_url = $api_url;
    }

    /**
     * Register a user.
     *
     * @param  string    $email        New user's email
     * @param  string    $cellphone    New user's cellphone
     * @param  int       $country_code New user's country code. defaults to USA(1)
     * @param  boolean   $send_install_link_via_sms send authy app link via sms if true
     * @return AuthyUser the new registered user
     */
     public function registerUser($email, $cellphone, $country_code = 1, $send_install_link_via_sms = false) {
         $resp = $this->rest->post('users/new', array(
             'query' => array(
                 'user' => array(
                     "email" => $email,
                     "cellphone" => $cellphone,
                     "country_code" => $country_code
                 ),
                 'send_install_link_via_sm' => $send_install_link_via_sms == true ? true : false
             )
         ));

         return new AuthyUser($resp);
     }

    /**
     * Verify a given token.
     *
     * @param string $authy_id User's id stored in your database
     * @param string $token    The token entered by the user
     * @param array  $opts     Array of options, for example: array("force" => "true")
     *
     * @return AuthyResponse the server response
     */
    public function verifyToken($authy_id, $token, $opts = array()) {
        $params = [];

        if (!array_key_exists("force", $opts)) {
            $params["force"] = "true";
        }

        $token = urlencode($token);
        $authy_id = urlencode($authy_id);
        $this->__validateVerify($token, $authy_id);

        try {
            $resp = $this->rest->get("verify/{$token}/{$authy_id}", array(
                'query' => $params
            ));
        } catch (Exception $e) {
            throw new AuthyFormatException($e->getMessage());
        }

        return new AuthyToken($resp);
    }

    /**
     * Request a valid token via SMS.
     *
     * @param string $authy_id User's id stored in your database
     * @param array  $opts     Array of options, for example: array("force" => "true")
     *
     * @return AuthyResponse the server response
     */
    public function requestSms($authy_id, $opts = array()) {
        $authy_id = urlencode($authy_id);
        $resp = $this->rest->get("sms/{$authy_id}", array(
            'query' => $opts
        ));

        return new AuthyResponse($resp);
    }

    /**
     * Cellphone call, usually used with SMS Token issues or if no smartphone is available.
     * This function needs the app to be on Starter Plan (free) or higher.
     *
     * @param string $authy_id User's id stored in your database
     * @param array  $opts     Array of options, for example: array("force" => "true")
     *
     * @return AuthyResponse the server response
     */
    public function phoneCall($authy_id, $opts = array()) {
        $authy_id = urlencode($authy_id);
        $resp = $this->rest->get("call/{$authy_id}", array(
            'query' => $opts
        ));

        return new AuthyResponse($resp);
    }

    /**
     * Deletes an user.
     *
     * @param string $authy_id User's id stored in your database
     *
     * @return AuthyResponse the server response
     */
    public function deleteUser($authy_id) {
        $authy_id = urlencode($authy_id);
        $resp = $this->rest->post("users/delete/{$authy_id}");
        return new AuthyResponse($resp);
    }

    /**
     * Gets user status.
     *
     * @param string $authy_id User's id stored in your database
     *
     * @return AuthyResponse the server response
     */
    public function userStatus($authy_id) {
        $authy_id = urlencode($authy_id);
        $resp = $this->rest->get("users/{$authy_id}/status");
        return new AuthyResponse($resp);
    }

    /**
     * Starts phone verification. (Sends token to user via sms or call).
     *
     * @param string $phone_number User's phone_number stored in your database
     * @param string $country_code User's phone country code stored in your database
     * @param string $via The method the token will be sent to user (sms or call)
     *
     * @return AuthyResponse the server response
     */
    public function phoneVerificationStart($phone_number, $country_code, $via = 'sms', $locale = null) {
        $query = array(
            "phone_number" => $phone_number,
            "country_code" => $country_code,
            "via" => $via
        );

        if ($locale != null)
            $query["locale"] = $locale;

        $resp = $this->rest->post("phones/verification/start", array('query' => $query));

        return new AuthyResponse($resp);
    }

    /**
     * Phone verification check. (Checks whether the token entered by the user is valid or not).
     *
     * @param string $phone_number User's phone_number stored in your database
     * @param string $country_code User's phone country code stored in your database
     * @param string $verification_code The verification code entered by the user to be checked
     *
     * @return AuthyResponse the server response
     */
    public function phoneVerificationCheck($phone_number, $country_code, $verification_code) {
        $resp = $this->rest->get("phones/verification/check", array(
            'query' => array(
                "phone_number" => $phone_number,
                "country_code" => $country_code,
                "verification_code" => $verification_code
            )
        ));

        return new AuthyResponse($resp);
    }

    /**
     * OneTouch verification request. Sends a request for Auth App. For more info https://www.twilio.com/docs/api/authy/authy-onetouch-api
     *
     * @param string $authy_id User's id stored in your database
     * @param string $message Required, the message shown to the user when the approval request arrives.
     * @param number $expires_in Optional, defaults to 86400 (one day).
     * @param array $details For example $details['Requested by'] = 'MacBook Pro, Chrome'; it will be displayed on Authy app
     * @param array $hidden_details Same usage as $detail except this detail is not shown in Authy app
     * @param array $logos Contains the logos that will be shown to user. The logos parameter is expected to be an array of objects, each object with two fields: res (values are default,low,med,high) and url
     *
     * @return AuthyResponse the server response
     */
    public function oneTouchVerificationRequest($authy_id, $message = null, $expires_in = null, $details = array(), $hidden_details = array(), $logos = array()) {
      if( !is_numeric($authy_id) || $authy_id <= 0 ){
        throw new AuthyFormatException("Invalid authy id");
      }
      if( empty($message) ){
        throw new AuthyFormatException("Invalid message - should not be empty. It is required");
      }

      if( !empty($expires_in) and !is_numeric($expires_in) ){
        throw new AuthyFormatException("Invalid seconds_to_expire. 0 or positive integer required.");
      }

      $query = array(
          'message' => substr($message,0,MAX_STRING_SIZE),
          'details' => $this->__clean_array($details),
          'hidden_details' => $this->__clean_array($hidden_details),
          'seconds_to_expire' => $expires_in,
      );

      $logos = $this->__clean_logos($logos);
      // a little hack to build query for logos - GuzzleHttp is not building correct format when using multiple logo objects
      $logo_query = array();
      foreach ($logos as $logo) {
          $logo_query[] = 'logos[][res]=' . urlencode($logo['res']);
          $logo_query[] = 'logos[][url]=' . urlencode($logo['url']);
      }
      $logo_query = implode('&', $logo_query);

      $query_str = http_build_query($query) . '&' . $logo_query;

      $authy_id = urlencode($authy_id);
      $resp = $this->rest->post($this->api_url . "/onetouch/json/users/$authy_id/approval_requests", array(
          'query' => $query_str
      ));

      return new AuthyResponse($resp);
    }

    /**
     * OneTouch verification request. Sends a request for Auth App. For more info https://www.twilio.com/docs/api/authy/authy-onetouch-api
     *
     * @param string $authy_id User's id stored in your database
     * @param string $oneTouchRequestUUID Required, UUID generated by oneTouchVerificationRequest call
     *
     * @return AuthyResponse the server response
     */
    public function oneTouchVerificationCheck($authy_id, $oneTouchRequestUUID) {
        $authy_id = urlencode($authy_id);
        $oneTouchRequestUUID = urlencode($oneTouchRequestUUID);
        $resp = $this->rest->get($this->api_url . "/onetouch/json/approval_requests/" . $oneTouchRequestUUID);
        return new AuthyResponse($resp);
    }

    /**
     * Returns callback params
     *
     * @return AuthyResponse
     */
    public function oneTouchGetCallbackParams() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $postdata = file_get_contents("php://input");
            $params = json_decode($postdata, true);
        } else {
            $params = $_GET;
        }
        return $params;
    }

    /**
     * Private fucntion to validate signature in X-Authy-Signature key of headers
     *
     * @param string $signature X-Authy-Signature key of headers
     * @param string $nonce X-Authy-Signature-Nonce key of headers
     * @param string $method GET or POST - configured in app settings for OneTouch
     * @param string $url base callback url
     * @param string $params params sent by Authy
     *
     * @return boolean
     */


     public function validateOneTouchSignature($signature, $nonce, $method, $url, $params) {
         $sorted_params = http_build_query($this->_sort_params($params));
         $data = $nonce."|".$method."|".$url."|".$sorted_params;
         $calculated_signature = base64_encode(hash_hmac('sha256', $data,$this->api_key,true));
         return $calculated_signature == $signature;
     }

    /**
     * converts if boolean true/false to string 'true' or 'false'
     *
     * * @param mixed $value
     *
     * @return string
     */
    private function _check_bool($value) {
        if (is_bool($value)) {
            $value = ($value) ? 'true' : 'false';
        } else {
            $value = (is_null($value)) ? '' : $value;
        }
        return $value;
    }

    /**
     * Sort params in case-sensitive order
     *
     * * @param mixed $value
     *
     * @return array
     */
    private function _sort_params($params) {
        $new_params = array();
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                ksort($v);
                $new_params[$k] = $v;
                foreach ($v as $k2 => $v2) {
                    if (is_array($v2)) {
                        ksort($v2);
                        $new_params[$k][$k2] = $v2;
                        foreach ($v2 as $k3 => $v3) {
                            $v3 = $this->_check_bool($v3);
                            $new_params[$k][$k2][$k3] = $v3;
                        }
                    } else {
                        $v2 = $this->_check_bool($v2);
                        $new_params[$k][$k2] = $v2;
                    }
                }
            } else {
                $v = $this->_check_bool($v);
                $new_params[$k] = $v;
            }
        }
        ksort($new_params);
        return $new_params;
    }

    /**
     * Register some of the activities that your user do on your application
     *
     * @param string $authy_id User's id stored in your database
     * @param string $activity activity to record (values can be 'password_rese', 'banned', 'unbanned', 'cookie_login')
     * @param array $data Data that you want to associate with this activity
     * @param string $IP IP of the user that is doing the request. Both IPv4 and IPv6 are supported.
     *
     * @return AuthyResponse the server response
     */
    public function registerUserActivity($authy_id, $activity, $data = null, $IP = null) {
        $authy_id = urlencode($authy_id);
        $resp = $this->rest->post("users/$authy_id/register_activity", array(
            'query' => array(
                'type' => $activity,
                'data' => $data,
                'user_ip' => $IP
            )
        ));
        return new AuthyResponse($resp);
    }

    /**
     * This call will retrieve the application details such as Name, Plan & SMS-enabled, OneTouch-enabled & App ID
     *
     * @return AuthyResponse the server response
     */
    public function appDetails() {
        $resp = $this->rest->get("app/details");
        return new AuthyResponse($resp);
    }

    /**
     * This call will retrieve the application stats by month in an Array. You can use this call to know App Quotas.
     *
     * @return AuthyResponse the server response
     */
    public function appStats() {
        $resp = $this->rest->get("app/stats");
        return new AuthyResponse($resp);
    }

    /**
     * Phone information
     *
     * @param string $phone_number User's phone_number stored in your database
     * @param string $country_code User's phone country code stored in your database
     *
     * @return AuthyResponse the server response
     */
    public function phoneInfo($phone_number, $country_code) {
        try {
            $resp = $this->rest->get("phones/info", array(
                'query' => array(
                    "phone_number" => $phone_number,
                    "country_code" => $country_code
                )
            ));
        } catch (Exception $e) {

        }

        return new AuthyResponse($resp);
    }

    private function __getUserAgent() {
        return sprintf(
                'AuthyPHP/%s (%s-%s-%s; PHP %s)', AuthyApi::VERSION, php_uname('s'), php_uname('r'), php_uname('m'), phpversion()
        );
    }

    private function __array_to_str( $arr ){
      if( !is_array($arr) ){
        return $arr;
      }

      $return = array();
      foreach($arr as $k=>$v){
        if( is_numeric($k) ){
          $return[] = $this->__array_to_str($v);
        }else{
          $return[] = "$k: " . $this->__array_to_str($v);
        }
      }
      $return = implode(', ',$return);
      $return .= "\n";
      return $return;
    }

    private function __clean_array( $arr ){
      if( empty($arr) ){
        return null;
      }

      foreach($arr as $k=>$v){
          $arr[$k] = !is_array($v) ? substr($v,0,MAX_STRING_SIZE) : $this->__array_to_str($v);
      }

      return $arr;
    }

    private function __clean_logos($logos){
        if( empty($logos) ){
          return array();
        }

        if( !is_array($logos) ){
          throw new AuthyFormatException("Invalid logos format. Array of logo is expected");
        }

        foreach($logos as $k=>$logo){
          if( !isset($logo['url']) || !isset($logo['res']) ) {
            throw new AuthyFormatException("Invalid logo array. 'url' or 'res' in logo object is missing");
          }

          if( is_array($logo['url']) || is_array($logo['res']) ) {
            throw new AuthyFormatException("Invalid logo array. 'url' or 'res' must be a string not more than ".MAX_STRING_SIZE.' length');
          }

          $logos[$k]['url'] = substr($logo['url'],0,MAX_STRING_SIZE);
          $logos[$k]['res'] = substr($logo['res'],0,MAX_STRING_SIZE);
        }

        return $logos;
    }

    private function __validateVerify($token, $authy_id) {
        $this->__validate_digit($token, "Invalid Token. Only digits accepted.");
        $this->__validate_digit($authy_id, "Invalid Authy id. Only digits accepted.");
        $length = strlen((string) $token);
        if ($length < MIN_TOKEN_SIZE or $length > MAX_TOKEN_SIZE) {
            throw new AuthyFormatException("Invalid Token. Unexpected length.");
        }
    }

    private function __validate_digit($var, $message) {
        if (!is_int($var) && !is_numeric($var)) {
            throw new AuthyFormatException($message);
        }
    }

}
