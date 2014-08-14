<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_JSONRPC_Exception_HTTP extends JSONRPC_Exception {

    public function __construct($message, HTTP_Exception $exception)
    {
        $this->code = $exception->getCode();

        parent::__construct($message, NULL, $exception);
    }

}
