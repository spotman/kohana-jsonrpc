<?php

interface JSONRPC_ModelResponseInterface
{
    /**
     * @return mixed
     */
    public function getJsonRpcResponseData();

    /**
     * @return \DateTimeInterface|null
     */
    public function getJsonRpcResponseLastModified();
}
