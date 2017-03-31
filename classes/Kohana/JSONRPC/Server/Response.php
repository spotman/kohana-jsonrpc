<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_JSONRPC_Server_Response
{
    /**
     * @var int
     */
    protected $_id;

    protected $_result;

    /**
     * @var \DateTime|null
     */
    protected $_last_modified;

    /**
     * @var JSONRPC_Exception
     */
    protected $_error;

    public static function factory()
    {
        return new static;
    }

    public function id($id)
    {
        $this->_id = $id;

        return $this;
    }

    public function succeeded($result)
    {
        $this->_result = $result;

        return $this;
    }

    public function failed(JSONRPC_Exception $error)
    {
        $this->_error = $error;

        return $this;
    }

    public function set_last_modified(DateTime $time)
    {
        $this->_last_modified = $time;
        return $this;
    }

    public function get_last_modified()
    {
        return $this->_last_modified;
    }

    public function body()
    {
        $response          = new stdClass;
        $response->jsonrpc = '2.0';
        $response->id      = $this->_id;

        // There is a error
        if ($this->_error) {
            $error = new stdClass;

            $error->code    = $this->_error->getCode();
            $error->message = (string)$this->_error->getMessage();

            $response->error = $error;
        } // Notifications does not need response
        elseif (!$this->_id) {
            return '';
        } else {
            $response->result = $this->_result;
        }

        return json_encode($response);
    }
}
