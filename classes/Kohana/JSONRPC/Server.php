<?php defined('SYSPATH') OR die('No direct script access.');

abstract class Kohana_JSONRPC_Server
{
    /**
     * @var callable
     */
    protected $proxy_factory_callable;

    /**
     * @var string[]
     */
    protected $access_violation_exceptions;

    /**
     * @var Response
     */
    protected $_response;

    public function __construct(Response $response)
    {
        $this->_response = $response;

        $this->register_proxy_factory([$this, 'default_proxy_factory']);
    }

    public function register_proxy_factory(callable $factory)
    {
        $this->proxy_factory_callable = $factory;

        return $this;
    }

    public function add_access_violation_exception($class_name)
    {
        $this->access_violation_exceptions[] = $class_name;
        return $this;
    }

    public static function factory(Response $response)
    {
        return new static($response);
    }

    public function default_proxy_factory($class_name)
    {
        if (!class_exists($class_name)) {
            throw new JSONRPC_Exception_MethodNotFound;
        }

        return new $class_name;
    }

    public function process($body = null)
    {
        $response      = null;
        $last_modified = null;

        try {
            // Get request
            $request = JSONRPC_Server_Request::factory();

            // Parse and validate request
            $request->parse($body);

            if ($request->is_batch()) {
                $batch_data    = $this->process_batch($request);
                $batch_results = [];

                // Update last modified for each item
                foreach ($batch_data as $item) {
                    $last_modified   = $this->update_last_modified($last_modified, $item->get_last_modified());
                    $batch_results[] = $item->body();
                }

                $response = '[' . implode(',', array_filter($batch_results)) . ']';
            } else {
                $data          = $this->process_request($request);
                $last_modified = $this->update_last_modified($last_modified, $data->get_last_modified());
                $response      = $data->body();
            }
        } catch (\Exception $e) {
            $this->process_exception($e);

            if ($this->is_access_violation_exception($e)) {
                // Access violation, throw 403
                $e = new HTTP_Exception_403('Access denied', [], $e);
            }

            $message = $this->get_exception_message($e);

            if ($e instanceof HTTP_Exception) {
                // Common HTTP exception (transfers HTTP code to response)
                $e = new JSONRPC_Exception_HTTP($message, $e);
            } elseif (!($e instanceof JSONRPC_Exception)) {
                // Wrap unknown exception into InternalError
                $e = new JSONRPC_Exception_InternalError($message, null, $e);
            }

            $response = JSONRPC_Server_Response::factory()
                ->failed($e)
                ->body();
        }

        // Send response
        $this->send_response($response, $last_modified);
    }

    protected function is_access_violation_exception($e)
    {
        foreach ($this->access_violation_exceptions as $violation_exception) {
            if ($e instanceof $violation_exception) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \JSONRPC_Server_Request $batch_request
     *
     * @return \JSONRPC_Server_Response[]
     */
    protected function process_batch(JSONRPC_Server_Request $batch_request)
    {
        $batch_results = [];

        // Process each request
        foreach ($batch_request as $sub_request) {
            $batch_results[] = $this->process_request($sub_request);
        }

        return array_filter($batch_results);
    }

    /**
     * @param JSONRPC_Server_Request $request
     *
     * @return \JSONRPC_Server_Response
     */
    protected function process_request(JSONRPC_Server_Request $request)
    {
        // Get class/method names
        $class_name  = $request->class_name();
        $method_name = $request->method_name();

        // Factory proxy object
        $proxy_object = $this->proxy_factory($class_name);

        $params = $this->prepare_params($proxy_object, $method_name, $request->params() ?: []);

        // Call proxy object method
        $result        = call_user_func_array([$proxy_object, $method_name], $params);
        $last_modified = null;

        if (is_object($result) && $result instanceof JSONRPC_ModelResponseInterface) {
            $last_modified = $result->getJsonRpcResponseLastModified();
            $result        = $result->getJsonRpcResponseData();
        }

        if (!$last_modified) {
            $last_modified = new DateTime;
        }

        // Make response
        return JSONRPC_Server_Response::factory()
            ->id($request->id())
            ->succeeded($result)
            ->set_last_modified($last_modified);
    }

    protected function proxy_factory($class_name)
    {
        return call_user_func($this->proxy_factory_callable, $class_name);
    }

    protected function prepare_params($proxy_object, $method_name, array $args)
    {
        if (!$args) {
            return $args;
        }

        // Thru indexed params
        if (is_int(key($args))) {
            return $args;
        }

        $reflection = new ReflectionMethod($proxy_object, $method_name);

        $params = [];

        foreach ($reflection->getParameters() as $param) {
            /* @var $param ReflectionParameter */
            if (isset($args[$param->getName()])) {
                $params[] = $args[$param->getName()];
            } elseif ($param->isDefaultValueAvailable()) {
                $params[] = $param->getDefaultValue();
            } else {
                throw new JSONRPC_Exception_InvalidParams;
            }
        }

        return $params;
    }

    protected function update_last_modified(DateTime $currentTime = null, DateTime $updatedTime = null)
    {
        if (!$currentTime || ($updatedTime && $updatedTime > $currentTime)) {
            $currentTime = $updatedTime;
        }

        return $currentTime;
    }

    /**
     * Process exception logging, notifications, etc
     *
     * @param Exception $e
     *
     * @return void
     * @throws Exception
     */
    protected function process_exception(Exception $e)
    {
//        $in_production = in_array(Kohana::$environment, [Kohana::PRODUCTION, Kohana::STAGING], true);

//        if (!$in_production && !($e instanceof HTTP_Exception)) {
//            throw $e;
//        }

        Kohana_Exception::log($e);
    }

    /**
     * @param Exception $e
     *
     * @return string
     */
    protected function get_exception_message(Exception $e)
    {
        // Hide original message on production environment
        return Kohana::$environment !== Kohana::PRODUCTION
            ? $e->getMessage()
            : 'Internal error';
    }

    protected function send_response($response, DateTime $last_modified = null)
    {
        if (!$last_modified) {
            $last_modified = new DateTime;
        }

        $value = gmdate("D, d M Y H:i:s \G\M\T", $last_modified->getTimestamp());

        $this->_response
            ->headers('content-type', 'application/json')
            ->headers('last-modified', $value)
            ->body($response);
    }
}
