<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_JSONRPC_Exception_ParseError extends JSONRPC_Exception {

    protected $code = self::PARSE_ERROR;

}
