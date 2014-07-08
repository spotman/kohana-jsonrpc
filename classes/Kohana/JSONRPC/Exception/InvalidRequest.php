<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_JSONRPC_Exception_InvalidRequest extends JSONRPC_Exception {

    protected $code = self::INVALID_REQUEST;

}
