<?php

$test_data = array(
    'api_key' => "bf12974d70818a08199d17d5e2bae630",
    'api_host' => "http://sandbox-api.authy.com",
    'email' => 'user@example.com',
    'mobile_number' => '305-456-2345', //'305-456-2345'
    'country_code' => 1,
    'authy_id' => 0,
    'invalid_token' => '1234567',
    'valid_token' => '0000000'
);

$ot_test_data = array(
    'api_key' => "YOUR API KEY",
    'api_host' => "https://api.authy.com",
    'email' => 'user@example.com',
    'mobile_number' => '305-456-2345', //'305-456-2345'
    'country_code' => 1,
    'authy_id' => 123456789
);

// OneTouch Callback mock data
define('OT_API_KEY','mTIn3Dq5HrF7r84rNTA0OrHys077egq3');
function test_getCallbackData($method='GET'){
  $get_data = array(
    'api_key' => OT_API_KEY,
    'method' => 'POST',
    'url' => 'http://c9b4c941.ngrok.io/authy/authy-php/src/callback.php',
    'params' => '{"device_uuid":"cea50e20-3aeb-0133-f92a-34363b620e52","callback_action":"approval_request_status","uuid":"cdbabf40-1c65-0133-d113-34363b620e52","status":"approved","approval_request":{"transaction":{"details":{"Email Address":"jdoe@example.com"},"device_details":[],"device_geolocation":"","device_signing_time":"946641599","encrypted":false,"flagged":false,"hidden_details":{"ip":"1.1.1.1"},"message":"Request to Login","reason":"","requester_details":"","status":"approved","uuid":"cdbabf40-1c65-0133-d113-34363b620e52","created_at_time":"946641599","customer_uuid":"25e026b36343-a29f-3310-bea3-02e05aec"},"logos":"","expiration_timestamp":"946641599"},"signature":"rzqf\/n08coE0Vi7IjbzAbt0IYMprJGAUx18kSJWE37K0mhvCGwepkm\/pSDXuSs+5kSUFK80L9RT7\/BZ7YwojSt5WhPnpRSImm5qKlvsNnGOPYCKVcFJxXCNJhtaztL\/2BjOMzdC5yNHH5uJIDGBhlb5fLVErsvauvxXWo\/Cj2STfITdSPULFz6XcbM1BDIriW7kP0GkELfUqE1iEuONEdhKYmPGolh3\/U4t8i0NYkQSPhbOGG1DZEsxhnxtelyBNOGK9sFojTsAg7dWesRYnyDkjTHZ1MvggdZwXo4qxphrY2Ve7+o04EHPZW9RPvakwl9yQ6rVsspVF\/xZT14BsgA==","authy_id":1234}',
    'nonce' => '1486660268',
    'signature' => '0Fl2yGPwgjhQCuiIyQXTaL56BL8ptyjso1kVgau5q1s='

  );

  $post_data = array(
    'api_key' => OT_API_KEY,
    'method' => 'GET',
    'url' => 'http://c9b4c941.ngrok.io/authy/authy-php/src/callback.php',
    'params' => '{"approval_request":{"expiration_timestamp":"946641599","logos":"","transaction":{"created_at_time":"946641599","customer_uuid":"25e026b36343-a29f-3310-bea3-02e05aec","details":{"Email Address":"jdoe@example.com"},"device_geolocation":"","device_signing_time":"946641599","encrypted":"false","flagged":"false","hidden_details":{"ip":"1.1.1.1"},"message":"Request to Login","reason":"","requester_details":"","status":"approved","uuid":"cdbabf40-1c65-0133-d113-34363b620e52"}},"authy_id":"1234","callback_action":"approval_request_status","device_uuid":"cea50e20-3aeb-0133-f92a-34363b620e52","signature":"rzqf\/n08coE0Vi7IjbzAbt0IYMprJGAUx18kSJWE37K0mhvCGwepkm\/pSDXuSs+5kSUFK80L9RT7\/BZ7YwojSt5WhPnpRSImm5qKlvsNnGOPYCKVcFJxXCNJhtaztL\/2BjOMzdC5yNHH5uJIDGBhlb5fLVErsvauvxXWo\/Cj2STfITdSPULFz6XcbM1BDIriW7kP0GkELfUqE1iEuONEdhKYmPGolh3\/U4t8i0NYkQSPhbOGG1DZEsxhnxtelyBNOGK9sFojTsAg7dWesRYnyDkjTHZ1MvggdZwXo4qxphrY2Ve7+o04EHPZW9RPvakwl9yQ6rVsspVF\/xZT14BsgA==","status":"approved","uuid":"cdbabf40-1c65-0133-d113-34363b620e52"}',
    'nonce' => '1486660308',
    'signature' => 'DMuAaWa7hoYwficR8YAar18VOphbTWeRIYTi1UXVzSo='

  );

  if( $method == 'GET' ){
    return $get_data;
  }else{
    return $post_data;
  }

}
