<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_JSONRPC_Server_Response {

    /**
     * @var int
     */
    protected $_id;

    protected $_result;

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

    public function body()
    {
        $response = new stdClass;
        $response->id = $this->_id;

        // There is a error
        if ( $this->_error )
        {
            $error = new stdClass;

            $error->code = $this->_error->getCode();
            $error->message = (string) $this->_error->getMessage();

//            $additional_data = $this->get_additional_error_data();
//
//            if ( $additional_data )
//            {
//                $error->data = $additional_data;
//            }

            $response->error = $error;
        }
        // Notifications does not need response
        else if ( ! $this->_id )
        {
            return '';
        }
        else
        {
            $response->result = $this->_result;
        }

        return json_encode($response);
    }

    protected function get_additional_error_data()
    {
        $data = array();

        $e = $this->_error->getPrevious();

        if ( $e )
        {
            $data['exception']  = array(
                'message'       =>  '['.$e->getCode().']'.$e->getMessage(),
                'file'          =>  $e->getFile().' at '.$e->getLine(),
                'trace'         =>  $e->getTrace(),
            ); //$exception;
        }

        return $data;
    }

}
