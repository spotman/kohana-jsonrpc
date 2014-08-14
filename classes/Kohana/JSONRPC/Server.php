<?php defined('SYSPATH') OR die('No direct script access.');

abstract class Kohana_JSONRPC_Server {

    protected $_proxy_factory_callback;

    /**
     * @var Response
     */
    protected $_response;

    public static function factory(Response $response)
    {
        return new static($response);
    }

    function __construct(Response $response)
    {
        $this->_response = $response;

        $this->register_proxy_factory(array($this, 'default_proxy_factory'));
    }

    public function register_proxy_factory($callback)
    {
        if ( ! is_callable($callback) )
            throw new JSONRPC_Exception('Callback must be callable');

        $this->_proxy_factory_callback = $callback;
        return $this;
    }

    public function default_proxy_factory($class_name)
    {
        if ( ! class_exists($class_name) )
            throw new JSONRPC_Exception_MethodNotFound;

        return new $class_name;
    }

    public function process($body = NULL)
    {
        $response = NULL;

        try
        {
            // Get request
            $request = JSONRPC_Server_Request::factory();

            // Parse and validate request
            $request->parse($body);

            $response = $request->is_batch()
                ? $this->process_batch($request)
                : $this->process_request($request);
        }
        catch ( Exception $e )
        {
            $message = $this->process_exception($e);

            // Common HTTP exception (transfers HTTP code to response)
            if ( $e instanceof HTTP_Exception )
            {
                $e = new JSONRPC_Exception_HTTP($message, $e);
            }
            else if ( ! ($e instanceof JSONRPC_Exception) )
            {
                // Wrap unknown exception into InternalError
                $e = new JSONRPC_Exception_InternalError($message, NULL, $e);
            }

            $response = JSONRPC_Server_Response::factory()
                ->failed($e)
                ->body();
        }

        // Send response
        $this->send_response($response);
    }

    /**
     * Process exception logging, notifications, etc
     *
     * @param Exception $e
     * @return string Exception message
     */
    protected function process_exception(Exception $e)
    {
        Kohana_Exception::log($e);

        return Kohana::in_production()
            ? NULL
            : $e->getMessage();
    }

    protected function process_batch($batch_request)
    {
        $batch_results = array();

        // Process each request
        foreach ( $batch_request as $sub_request )
        {
            $batch_results[] = $this->process_request($sub_request);
        }

        return '['. implode(',', array_filter($batch_results)) .']';
    }

    /**
     * @param JSONRPC_Server_Request $request
     * @return string
     */
    protected function process_request(JSONRPC_Server_Request $request)
    {
        // Get class/method names
        $class_name = $request->class_name();
        $method_name = $request->method_name();

        // Factory proxy object
        $proxy_object = $this->proxy_factory($class_name);

        $params = $this->prepare_params($proxy_object, $method_name, $request->params() ?: array());

        // Call proxy object method
        $result = call_user_func_array(array($proxy_object, $method_name), $params);

        $result = $this->process_result($result);

        // Make response
        return JSONRPC_Server_Response::factory()
            ->id($request->id())
            ->succeeded($result)
            ->body();
    }

    /**
     * Override this if you need to post-process proxy method result data
     *
     * @param $result
     * @return mixed
     */
    protected function process_result($result)
    {
        // Nothing by default
        return $result;
    }

    protected function prepare_params($proxy_object, $method_name, array $args)
    {
        if ( ! $args )
            return $args;

        // Thru indexed params
        if ( is_int(key($args)) )
            return $args;

        $reflection = new ReflectionMethod($proxy_object, $method_name);

        $params = array();

        foreach ( $reflection->getParameters() as $param )
        {
            /* @var $param ReflectionParameter */
            if ( isset($args[$param->getName()]) )
            {
                $params[] = $args[$param->getName()];
            }
            else if ( $param->isDefaultValueAvailable() )
            {
                $params[] = $param->getDefaultValue();
            }
            else
                throw new JSONRPC_Exception_InvalidParams;
        }

        return $params;
    }

    protected function proxy_factory($class_name)
    {
        return call_user_func($this->_proxy_factory_callback, $class_name);
    }

    protected function send_response($response)
    {
        $this->_response
            ->headers('content-type', 'application/json')
            ->body($response);
    }

}
