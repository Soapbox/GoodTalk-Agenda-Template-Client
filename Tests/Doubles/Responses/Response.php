<?php

namespace Tests\Doubles\Responses;

use GuzzleHttp\Psr7\Response as GuzzleResponse;

abstract class Response extends GuzzleResponse
{
    /**
     * @var array
     */
    private $data;

    /**
     * Create a new Stripe Response object
     *
     * @param array $data
     * @param int $status
     */
    public function __construct(array $data, int $status = 200)
    {
        $this->data = $data;
        parent::__construct($status, [], json_encode($data));
    }
}
