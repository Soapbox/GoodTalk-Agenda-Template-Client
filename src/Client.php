<?php

namespace SoapBox\AgendaTemplateClient;

use Illuminate\Http\Response;
use Illuminate\Config\Repository;
use JSHayes\FakeRequests\ClientFactory;
use GuzzleHttp\Exception\RequestException;
use SoapBox\AgendaTemplateClient\RemoteResources\AgendaTemplate;
use SoapBox\SignedRequests\Middlewares\Guzzle\GenerateSignature;
use SoapBox\AgendaTemplateClient\Exceptions\ItemNotFoundException;
use SoapBox\SignedRequests\Configurations\RepositoryConfiguration;
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
            'base_uri' => config('agenda-template-client.base_url'),
            'headers' => [
                'Accept' => 'application/json',
            ],
            'connect_timeout' => config('agenda-template-client.http.connect_timeout'),
            'timeout' => config('agenda-template-client.http.timeout'),
        ]);

        $configuration = new RepositoryConfiguration(new Repository(config('agenda-template-client')));
        $middleware = new GenerateSignature($configuration);
        $this->client->getConfig('handler')->push($middleware, 'generate_signature');
    }

    /**
     * Retrieve an agenda using slug using agenda template API
     *
     * @param string template slug $slug
     *
     * @return agenda template
     * @throws AgendaTemplateNotFoundException
     */
    public function getAgendaTemplate(string $slug): AgendaTemplate
    {
        try {
            $response = $this->client->get("agenda-templates/{$slug}");
        } catch (RequestException $exception) {
            throw new AgendaTemplateNotFoundException();
        }

        return (new Parser($response->getBody()->getContents()))->getAgendaTemplate();
    }

    public function getRecentlyAddedOrUpdatedItems(string $date)
    {
        try {
            $response = $this->client->get("items?date={$date}");
        } catch (RequestException $exception) {
            throw new ItemNotFoundException();
        }
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Make a request and transform the guzzle response into an illuminate response
     *
     * @throws \GuzzleHttp\Exception\RequestException
     * Thrown if a response was not returned from the agenda template service
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     *
     * @return \Illuminate\Http\Response
     */
    private function makeRequestAndReturnResponse(string $method, string $endpoint, array $data): Response
    {
        // default to a get if an invalid request verb is used
        if (!in_array($method, ['get', 'put', 'patch', 'post', 'delete'])) {
            $method = 'get';
        }

        try {
            $response = $this->client->{$method}($endpoint, ['json' => $data]);
        } catch (RequestException $exception) {
            // Return responses that have been turned into an exception
            if ($exception->hasResponse()) {
                $response = $exception->getResponse();
            } else {
                throw $exception;
            }
        }

        return new Response($response->getBody()->getContents(), $response->getStatusCode());
    }

    /**
     * Create an agenda template using agenda template API
     *
     * @throws \GuzzleHttp\Exception\RequestException
     * Thrown if a response was not returned from the agenda template service
     *
     * @param int $userId
     * @param int $soapboxId
     * @param int $data
     *
     * @return \Illuminate\Http\Response
     */
    public function createAgendaTemplate(int $userId, int $soapboxId, array $data): Response
    {
        $data['soapbox-user-id'] = $userId;
        $data['soapbox-id'] = $soapboxId;

        return $this->makeRequestAndReturnResponse("post", "agenda-templates", $data);
    }

    /**
     * Update an agenda template using agenda template API
     *
     * @throws \GuzzleHttp\Exception\RequestException
     * Thrown if a response was not returned from the agenda template service
     *
     * @param int $userId
     * @param int $agendaTemplateId
     * @param int $data
     *
     * @return \Illuminate\Http\Response
     */
    public function updateAgendaTemplate(int $userId, int $agendaTemplateId, array $data): Response
    {
        $data['soapbox-user-id'] = $userId;

        return $this->makeRequestAndReturnResponse("put", "agenda-templates/{$agendaTemplateId}", $data);
    }

    /**
     * Delete an agenda template using agenda template API
     *
     * @throws \GuzzleHttp\Exception\RequestException
     * Thrown if a response was not returned from the agenda template service
     *
     * @param int $userId
     * @param int $agendaTemplateId
     * @param int $data
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteAgendaTemplate(int $userId, int $agendaTemplateId, array $data): Response
    {
        $data['soapbox-user-id'] = $userId;

        return $this->makeRequestAndReturnResponse("delete", "agenda-templates/{$agendaTemplateId}", $data);
    }

    /**
     * Create an agenda template item using agenda template API
     *
     * @throws \GuzzleHttp\Exception\RequestException
     * Thrown if a response was not returned from the agenda template service
     *
     * @param int $userId
     * @param int $agendaTemplateId
     * @param int $data
     *
     * @return \Illuminate\Http\Response
     */
    public function createAgendaTemplateItem(int $userId, int $agendaTemplateId, array $data): Response
    {
        $data['soapbox-user-id'] = $userId;

        return $this->makeRequestAndReturnResponse("post", "agenda-templates/{$agendaTemplateId}/items", $data);
    }

    /**
     * Update an agenda template item using agenda template API
     *
     * @throws \GuzzleHttp\Exception\RequestException
     * Thrown if a response was not returned from the agenda template service
     *
     * @param int $userId
     * @param int $itemId
     * @param int $data
     *
     * @return \Illuminate\Http\Response
     */
    public function updateAgendaTemplateItem(int $userId, int $itemId, array $data): Response
    {
        $data['soapbox-user-id'] = $userId;

        return $this->makeRequestAndReturnResponse("put", "item/{$itemId}", $data);
    }

    /**
     * Delete an agenda template item using agenda template API
     *
     * @throws \GuzzleHttp\Exception\RequestException
     * Thrown if a response was not returned from the agenda template service
     *
     * @param int $userId
     * @param int $itemId
     * @param int $data
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteAgendaTemplateItem(int $userId, int $itemId, array $data): Response
    {
        $data['soapbox-user-id'] = $userId;

        return $this->makeRequestAndReturnResponse("delete", "item/{$itemId}", $data);
    }
}
