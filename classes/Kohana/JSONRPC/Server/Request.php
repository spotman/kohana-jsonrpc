<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_JSONRPC_Server_Request implements IteratorAggregate {

    /**
     * @var string
     */
    protected $_raw_body;

    /**
     * @var array
     */
    protected $_body;

    /**
     * @var array
     */
    protected $_batch_sub_requests = array();

    /**
     * @var int
     */
    protected $_id;

    /**
     * @var string
     */
    protected $_class_name;

    /**
     * @var string
     */
    protected $_method_name;

    /**
     * @var array
     */
    protected $_params;

    public static function factory()
    {
        return new static;
    }

    public function parse($body = NULL)
    {
        // Getting data
        $this->_raw_body = $body ?: file_get_contents('php://input');

        // Decoding raw data
        $this->_body = json_decode($this->_raw_body);

        if ( ! $this->_body )
            throw new JSONRPC_Exception_InvalidRequest;

        // Checking is current request is a batch request
        if ( is_array($this->_body) )
        {
            foreach ( $this->_body as $sub_request_data )
            {
                $this->_batch_sub_requests[] = $this->factory()->parse_request($sub_request_data);
            }
        }
        else
        {
            $this->parse_request($this->_body);
        }
    }

    protected function parse_request($raw_data)
    {
        // Check protocol version
        if ( ! isset($raw_data->jsonrpc) OR $raw_data->jsonrpc != '2.0' )
            throw new JSONRPC_Exception_InvalidRequest;

        $this->_id = isset($raw_data->id) ? (int) $raw_data->id : NULL;

        $this->_params = isset($raw_data->params) ? (array) $raw_data->params : NULL;

        $raw_method = isset($raw_data->method) ? (string) $raw_data->method : NULL;

        if ( ! $raw_method )
            throw new JSONRPC_Exception_InvalidRequest;

        $raw_method_array = explode('.', $raw_method);

        if ( count($raw_method_array) != 2 )
            throw new JSONRPC_Exception_InvalidRequest;

        $this->_class_name = $raw_method_array[0];
        $this->_method_name = $raw_method_array[1];

        if ( ! $this->_class_name OR ! $this->_method_name )
            throw new JSONRPC_Exception_InvalidRequest;

        return $this;
    }

    public function class_name()
    {
        return $this->_class_name;
    }

    public function method_name()
    {
        return $this->_method_name;
    }

    public function params()
    {
        return $this->_params;
    }

    public function id()
    {
        return $this->_id;
    }

    /**
     * @return bool
     */
    public function is_batch()
    {
        return (bool) $this->_batch_sub_requests;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_batch_sub_requests);
    }

}
