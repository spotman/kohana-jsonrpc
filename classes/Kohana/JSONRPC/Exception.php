<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_JSONRPC_Exception extends Kohana_Exception {

    const PARSE_ERROR = -32700;
    const INVALID_REQUEST = -32600;
    const METHOD_NOT_FOUND = -32601;
    const INVALID_PARAMS = -32602;
    const INTERNAL_ERROR = -32603;

    protected $_messages = array(
        self::PARSE_ERROR       => 'Parse error',
        self::INVALID_REQUEST   => 'Invalid Request',
        self::METHOD_NOT_FOUND  => 'Method not found',
        self::INVALID_PARAMS    => 'Invalid params',
        self::INTERNAL_ERROR    => 'Internal error',
    );

    protected $_original_exception;

    public function __construct(Exception $original_exception = NULL)
    {
        $code = $this->code;
        $message = $this->_messages[$code];

        $this->_original_exception = $original_exception;

        parent::__construct($message, $variables = NULL, $code);
    }

    public function get_original_exception()
    {
        return $this->_original_exception;
    }

}
