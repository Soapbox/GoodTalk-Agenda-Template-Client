<?php

namespace SoapBox\AgendaTemplateClient;

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

        $configuration = resolve(RepositoryConfiguration::class);
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
     * Create an agenda template using agenda template API
     *
     * @param string template slug $slug
     *
     * @return agenda template
     * @throws AgendaTemplateNotFoundException
     */
    public function createAgendaTemplate(int $userId, int $soapboxId, array $data): Response
    {
        $data['soapbox-user-id'] = $userId;
        $data['soapbox-id'] = $soapboxId;
        try {
            return  $this->client->post("agenda-templates/", ['json' => $data]);
        } catch (RequestException $exception) {
            throw new AgendaTemplateNotFoundException();
        }
    }
}
