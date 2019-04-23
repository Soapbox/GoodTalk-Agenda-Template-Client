<?php

namespace SoapBox\AgendaTemplateClient;

use JSHayes\FakeRequests\ClientFactory;
use GuzzleHttp\Exception\RequestException;
use SoapBox\AgendaTemplateClient\RemoteResources\AgendaTemplate;
use SoapBox\AgendaTemplateClient\Exceptions\AgendaTemplateNotFoundException;

class Client
{
    /**
     * The guzzle client that is used to send the request
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * Create client instance that will issue requests to a remote API
     *
     * @param string $baseUri
     *        The base uri for the client
     * @param \JSHayes\FakeRequests\ClientFactory $factory
     *        The factory used to create the client
     * @param \SoapBox\SignedRequests\Middlewares\Guzzle\GenerateSignature $middleware
     *        The middleware that will be used to sign the request
     */
    public function __construct(ClientFactory $factory)
    {
        $this->client = $factory->make([
            'base_uri' => 'agenda-templates.services.soapboxdev.com/api/',
            'headers' => [
                'Accept' => 'application/json',
            ],
            'connect_timeout' => config('notifications.http.connect_timeout'),
            'timeout' => config('notifications.http.timeout'),
        ]);
    }

    public function getAgendaTemplate(int $id): AgendaTemplate
    {
        try {
            $response = $this->client->get("agenda-templates/{$id}");
        } catch (RequestException $exception) {
            throw new AgendaTemplateNotFoundException();
        }

        return (new Parser($response->getBody()->getContents()))->getAgendaTemplate();
    }
}
