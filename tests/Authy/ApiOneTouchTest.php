<?php
require_once __DIR__.'/TestHelper.php';

use Authy\AuthyApi;
use Authy\AuthyFormatException;

class ApiOneTouchTest extends \PHPUnit_Framework_TestCase
{
    private $client;
    private $invalid_token;
    private $valid_token;
    private $data;
    private $email;
    private $number;
    private $country_code;
    private $authy_id;

    //for oneTouch
    private $ot_client;
    private $ot_authy_id;


    public function setUp()
    {
        global $test_data;
        $this->data = $test_data;

        $this->client = new AuthyApi($this->data['api_key'],$this->data['api_host']);
        // print_r($this->client);exit();
        $this->invalid_token = $this->data['invalid_token'];
        $this->valid_token = $this->data['valid_token'];
        $this->email = $this->data['email'];
        $this->number = $this->data['mobile_number'];
        $this->country_code = $this->data['country_code'];
        $this->authy_id = $this->data['authy_id'];


        // for one touch
        global $ot_test_data;
        if( !empty($ot_test_data['api_key']) && !empty($ot_test_data['api_host']) ){
            $this->ot_client = new AuthyApi($ot_test_data['api_key'],$ot_test_data['api_host']);
            $this->ot_authy_id = $ot_test_data['authy_id'];
        }


    }


    public function testOneTouchRequestWithValidData()
    {
        $response = $this->ot_client->oneTouchVerificationRequest($this->ot_authy_id,'Verification for password reset',null,array(
            'Location' => 'San Francisco, CA'
        ),array(
            'IP' => '192.168.1.1'
        ),array(
            array(
                'url' => 'http://talks.php.net/presentations/slides/intro/php-logo-large.png',
                'res' => 'default'
            ),
            array(
                'url' => 'http://www.dynamicline.hu/documents/tartalomkezelo-rendszer/php_thumb.png',
                'res' => 'low'
            )
        ));

        $this->assertEquals(true, $response->ok());
        $this->assertEquals(true,isset($response->body()->approval_request->uuid));
    }

    public function testOneTouchRequestWithMinValidData()
    {
        $response = $this->ot_client->oneTouchVerificationRequest($this->ot_authy_id,'Verification for password reset');
        $this->assertEquals(true, $response->ok());
        $this->assertEquals(true,isset($response->body()->approval_request->uuid));
    }

    public function testOneTouchRequestWithoutMessage()
    {
        try{
          $response = $this->ot_client->oneTouchVerificationRequest($this->ot_authy_id);
        }catch(Exception $e){
          $this->assertRegExp('/Invalid message/i', $e->getMessage());
        }
    }

    public function testOneTouchRequestWithEmptyAndInvalidAuthyID()
    {
        try{
          $response = $this->ot_client->oneTouchVerificationRequest(0);
        }catch(Exception $e){
          $this->assertRegExp('/Invalid/i', $e->getMessage());
        }

        try{
          $response = $this->ot_client->oneTouchVerificationRequest('123456/1#','Password reset request');
        }catch(Exception $e){
          $this->assertRegExp('/Invalid/i', $e->getMessage());
        }
    }

    public function testOneTouchWithNoLogo(){
      $response = $this->ot_client->oneTouchVerificationRequest($this->ot_authy_id,'Password reset request',null,array(
          'Location' => 'San Francisco, CA'
      ),array(
          'IP' => '192.168.1.1',
      ),null);

      $this->assertEquals(true, $response->ok());
      $this->assertEquals(true,isset($response->body()->approval_request->uuid));
    }

    public function testOneTouchWithInvalidLogos1(){
      try{
        $response = $this->ot_client->oneTouchVerificationRequest($this->ot_authy_id,'Password reset request',null,array(
            'Location' => 'San Francisco, CA'
        ),array(
            'IP' => '192.168.1.1',
        ),array(
            array(
                'url' => 'http://talks.php.net/presentations/slides/intro/php-logo-large.png',
            ),
            array(
                'url' => 'http://www.dynamicline.hu/documents/tartalomkezelo-rendszer/php_thumb.png',
                'res' => 'low'
            )
        ));
      }catch(Exception $e){
        $this->assertRegExp('/Invalid logo array/i', $e->getMessage());
      }
    }

    public function testOneTouchWithInvalidLogos2(){
      try{
        $response = $this->ot_client->oneTouchVerificationRequest($this->ot_authy_id,'Password reset request',null,array(
            'Location' => 'San Francisco, CA'
        ),array(
            'IP' => '192.168.1.1',
        ),array(
            array(
                'url' => 'http://talks.php.net/presentations/slides/intro/php-logo-large.png',
                'res1' => 'default'
            ),
            array(
                'url' => 'http://www.dynamicline.hu/documents/tartalomkezelo-rendszer/php_thumb.png',
                'res' => array('low')
            )
        ));
      }catch(Exception $e){
        $this->assertRegExp('/Invalid logo array/i', $e->getMessage());
      }
    }

    public function testOneTouchWithInvalidLogos3(){
      try{
        $response = $this->ot_client->oneTouchVerificationRequest($this->ot_authy_id,'Password reset request',null,array(
            'Location' => 'San Francisco, CA'
        ),array(
            'IP' => '192.168.1.1',
        ),'http://talks.php.net/presentations/slides/intro/php-logo-large.png');
      }catch(Exception $e){
        $this->assertRegExp('/Invalid logos format/i', $e->getMessage());
      }
    }

    public function testOneTouchWithInvalidExpireSeconds(){
      try{
        $response = $this->ot_client->oneTouchVerificationRequest($this->ot_authy_id,'Password reset request','ABC',array(
            'Location' => 'San Francisco, CA'
        ),array(
            'IP' => '192.168.1.1',
        ));
      }catch(Exception $e){
        $this->assertRegExp('/Invalid seconds_to_expire/i', $e->getMessage());
      }

    }

    public function testOneTouchGetCallbackValidGETRequest(){
      $data = test_getCallbackData('GET');
      foreach($data as $k=>$v){
        $$k = $v;
      }
      $ot_client = new AuthyApi($api_key,$ot_test_data['api_host']);
      $params = json_decode($params,true);
      $is_valid = $this->ot_client->validateOneTouchSignature($signature, $nonce, $method, $url, $params);
      $this->assertEquals(true, $is_valid);
    }

    public function testOneTouchGetCallbackInvalidValidNonceGETRequest(){
      $data = test_getCallbackData('GET');
      foreach($data as $k=>$v){
        $$k = $v;
      }
      $nonce = 'INVALID STRING';
      $ot_client = new AuthyApi($api_key,$ot_test_data['api_host']);
      $params = json_decode($params,true);
      $is_valid = $this->ot_client->validateOneTouchSignature($signature, $nonce, $method, $url, $params);
      $this->assertEquals(false, $is_valid);
    }

    public function testOneTouchGetCallbackValidPOSTRequest(){
      $data = test_getCallbackData('POST');
      foreach($data as $k=>$v){
        $$k = $v;
      }
      $ot_client = new AuthyApi($api_key,$ot_test_data['api_host']);
      $params = json_decode($params,true);
      $is_valid = $this->ot_client->validateOneTouchSignature($signature, $nonce, $method, $url, $params);
      $this->assertEquals(true, $is_valid);
    }

    public function testOneTouchGetCallbackInvalidValidNoncePOSTRequest(){
      $data = test_getCallbackData('POST');
      foreach($data as $k=>$v){
        $$k = $v;
      }
      $nonce = 'INVALID STRING';
      $ot_client = new AuthyApi($api_key,$ot_test_data['api_host']);
      $params = json_decode($params,true);
      $is_valid = $this->ot_client->validateOneTouchSignature($signature, $nonce, $method, $url, $params);
      $this->assertEquals(false, $is_valid);
    }
}
