<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_JSONRPC_Exception_MethodNotFound extends JSONRPC_Exception {

    protected $code = self::METHOD_NOT_FOUND;

}
