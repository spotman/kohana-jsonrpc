<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_JSONRPC_Client {

    /**
     * @var Response
     */
    protected $_last_response;

    public static function factory()
    {
        return new static;
    }

    public function call($url, $method, array $params = NULL)
    {
        $payload = new stdClass;

        $payload->jsonrpc = '2.0';
        $payload->id = $this->generate_id();
        $payload->method = $method;

        if ( $params )
        {
            $payload->params = $params;
        }

        /** @var Request $request */
        $request = Request::factory($url, array())
//            ->client(Request_Client_External::factory(array(), 'Request_Client_Stream'))
            ->method(Request::POST)
            ->body(json_encode($payload))
            ->headers('Content-Type', 'application/json');

        $this->_last_response = $request->execute();

        $status = $this->_last_response->status();

        if ( $status !== 200 )
            throw new JSONRPC_Exception(
                'Response with :status status from :url -> :method',
                array(':status' => $status, ':url' => $url, ':method' => $method)
            );

        $data = json_decode($this->_last_response->body(), TRUE);

        if ( ! isset($data['id']) OR $data['id'] != $payload->id OR ! isset($data['result']) )
            throw new JSONRPC_Exception(
                'Incorrect response from :url -> :method',
                array(':url' => $url, ':method' => $method)
            );

        return $data['result'];
    }

    // TODO Move to Response
    public function get_last_modified()
    {
        $string = $this->_last_response->headers('Last-Modified');
        $format = 'D, d M Y H:i:s \G\M\T';

        return DateTime::createFromFormat($format, $string);
    }

    protected function generate_id()
    {
        return mt_rand(1, 100000000);
    }

}
