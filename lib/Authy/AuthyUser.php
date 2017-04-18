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
 * User implementation. Extends from Authy_Response
 *
 * @category Services
 * @package  Authy
 * @author   David Cuadrado <david@authy.com>
 * @license  http://creativecommons.org/licenses/MIT/ MIT
 * @link     http://authy.github.com/pear
 */
namespace Authy;

class AuthyUser extends AuthyResponse
{
    /**
     * Constructor.
     *
     * @param array $raw_response Raw server response
     */
    public function __construct($raw_response)
    {
      parent::__construct($raw_response);

      if (isset($this->body->user)) {
          // response is {user: {id: id}}
          $this->body = $this->body->user;
      }
      
    }
}
