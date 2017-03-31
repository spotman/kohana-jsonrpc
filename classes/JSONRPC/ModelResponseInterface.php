<?php

interface JSONRPC_ModelResponseInterface
{
    /**
     * @return mixed
     */
    public function getJsonRpcResponseData();

    /**
     * @return \DateTime|null
     */
    public function getJsonRpcResponseLastModified();
}
