<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_JSONRPC_Exception_InternalError extends JSONRPC_Exception {

    protected $code = self::INTERNAL_ERROR;

}
