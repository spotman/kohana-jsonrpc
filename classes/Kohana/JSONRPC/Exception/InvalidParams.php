<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_JSONRPC_Exception_InvalidParams extends JSONRPC_Exception {

    protected $code = self::INVALID_PARAMS;

}
