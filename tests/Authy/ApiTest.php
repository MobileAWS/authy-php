<?php
require_once __DIR__.'/TestHelper.php';

use Authy\AuthyApi;
use Authy\AuthyFormatException;

class ApiTest extends \PHPUnit_Framework_TestCase
{
    private $client;
    private $invalid_token;
    private $valid_token;
    private $data;
    private $email;
    private $number;
    private $country_code;
    private $authy_id;


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
    }

    public function testCreateUserWithValidData()
    {
        $user = $this->client->registerUser($this->email,$this->number,$this->country_code);
        $this->assertEquals("integer", gettype($user->id()));
        $this->assertEmpty((array) $user->errors());
    }

    public function testCreateUserWithInvalidData()
    {
        $user = $this->client->registerUser($this->email, '', $this->country_code);

        $this->assertEquals("NULL", gettype($user->id()));
        $this->assertNotEmpty((array) $user->errors());

        $errors = (array) $user->errors();

        $this->assertArrayHasKey("message", $errors);
        $this->assertArrayHasKey("cellphone", $errors);
        $this->assertEquals("User was not valid", $errors["message"]);
        $this->assertEquals("is invalid", $errors["cellphone"]);
    }

    public function testVerifyTokenWithValidUser()
    {
        $user = $this->client->registerUser($this->email, $this->number, $this->country_code);
        $token = $this->client->verifyToken($user->id(), $this->invalid_token);

        $this->assertEquals(false, $token->ok());
    }

    public function testVerifyTokenWithInvalidUser()
    {
        $token = $this->client->verifyToken(0, $this->invalid_token);

        $this->assertEquals(false, $token->ok());
        $this->assertNotEmpty((array) $token->errors());
        $this->assertEquals("User doesn't exist", $token->errors()->message);
    }

    public function testVerifyTokenWithInvalidToken()
    {
        $user = $this->client->registerUser($this->email, $this->number, $this->country_code);
        $token = $this->client->verifyToken($user->id(), $this->invalid_token);
        $this->assertEquals(false, $token->ok());
    }

    public function testVerifyTokenWithValidToken()
    {
        $user = $this->client->registerUser($this->email, $this->number, $this->country_code);
        $token = $this->client->verifyToken($user->id(), $this->valid_token);
        $this->assertEquals(true, $token->ok());
    }

    public function testVerifyTokenWithNonNumericToken()
    {
        $user = $this->client->registerUser($this->email, $this->number, $this->country_code);
        try {
            $token = $this->client->verifyToken($user->id(), '123456/1#');
        } catch (AuthyFormatException $e) {
            $this->assertEquals($e->getMessage(), 'Invalid Token. Only digits accepted.');
            return;
        }
        $this->fail('AuthyFormatException has not been raised.');
    }

    public function testVerifyTokenWithNonNumericAuthyId()
    {
        try {
            $token = $this->client->verifyToken('123456/1#', $this->valid_token);
        } catch (AuthyFormatException $e) {
            $this->assertEquals($e->getMessage(), 'Invalid Authy id. Only digits accepted.');
            return;
        }
        $this->fail('AuthyFormatException has not been raised.');
    }

    public function testVerifyTokenWithSmallerToken()
    {
        $user = $this->client->registerUser($this->email, $this->number, $this->country_code);
        try {
            $token = $this->client->verifyToken($user->id(), '12345');
        } catch (AuthyFormatException $e) {
            $this->assertEquals($e->getMessage(), 'Invalid Token. Unexpected length.');
            return;
        }
        $this->fail('AuthyFormatException has not been raised.');
    }

    public function testVerifyTokenWithLongerToken()
    {
        $user = $this->client->registerUser($this->email, $this->number, $this->country_code);
        try {
            $token = $this->client->verifyToken($user->id(), '1234567890123');
        } catch (AuthyFormatException $e) {
            $this->assertEquals($e->getMessage(), 'Invalid Token. Unexpected length.');
            return;
        }
        $this->fail('AuthyFormatException has not been raised.');
    }

    public function testRequestSmsWithInvalidUser()
    {
        $sms = $this->client->requestSms(0, array("force" => "true"));

        $this->assertEquals(false, $sms->ok());
    }

    public function testRequestSmsWithValidUser()
    {
        $user = $this->client->registerUser($this->email, $this->number, $this->country_code);
        $sms = $this->client->requestSms($user->id(), array("force" => "true"));

        $this->assertEquals(true, $sms->ok());
        //$this->assertEquals("is not activated for this account", $sms->errors()->enable_sms);
    }

    public function testPhonceCallWithInvalidUser()
    {
        $call = $this->client->phoneCall(0, array());

        $this->assertEquals(false, $call->ok());
        $this->assertEquals("User not found.", $call->errors()->message);
    }

    public function testPhonceCallWithValidUser()
    {
        $user = $this->client->registerUser($this->email, $this->number, $this->country_code);
        $call = $this->client->phoneCall($user->id(), array());

        $this->assertEquals(true, $call->ok());
        $this->assertRegExp('/Call started/i', $call->message());
    }

    public function testDeleteUserWithInvalidUser()
    {
        $response = $this->client->deleteUser(0);

        $this->assertEquals(false, $response->ok());
        $this->assertEquals("User not found.", $response->errors()->message);
    }

    public function testDeleteUserWithValidUser()
    {
        $user = $this->client->registerUser($this->email, $this->number, $this->country_code);
        $response = $this->client->deleteUser($user->id());

        $this->assertEquals(true, $response->ok());
    }

    public function testUserStatusWithInvalidUser()
    {
        $response = $this->client->userStatus(0);

        $this->assertEquals(false, $response->ok());
        $this->assertEquals("User not found.", $response->errors()->message);
    }

    public function testUserStatusWithValidUser()
    {
        $user = $this->client->registerUser($this->email, $this->number, $this->country_code);
        $response = $this->client->userStatus($user->id());

        $this->assertEquals(true, $response->ok());
    }

    public function testPhoneVerificationStartWithoutVia()
    {
        $response = $this->client->PhoneVerificationStart($this->number, $this->country_code);
        $this->assertEquals(true, $response->ok());
        $this->assertRegExp('/Text message sent/i', $response->message());
    }

    public function testPhoneVerificationStartWithVia()
    {
        $response = $this->client->PhoneVerificationStart($this->number, $this->country_code, 'call');

        $this->assertEquals(true, $response->ok());
        $this->assertRegExp('/Call to .* initiated/i', $response->message());
    }

    public function testPhoneVerificationCheck()
    {
        // it fails will sandbox enviroment but works on production
//        $response = $this->client->PhoneVerificationCheck($this->number, $this->country_code,$token);
//        $this->assertEquals(true, $response->ok());
//        $this->assertRegExp('/Verification code is correct/i', $response->message());
    }

    public function testPhoneInfo()
    {
        $response = $this->client->PhoneInfo($this->number, '1');
        $this->assertEquals(true, $response->ok());
    }
}
