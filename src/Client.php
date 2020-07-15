<?php

namespace SoapBox\AgendaTemplateClient;

use Illuminate\Http\Response;
use Illuminate\Config\Repository;
use JSHayes\FakeRequests\ClientFactory;
use GuzzleHttp\Exception\RequestException;
use SoapBox\AgendaTemplateClient\RemoteResources\SuggestedGoal;
use SoapBox\AgendaTemplateClient\RemoteResources\AgendaTemplate;
use SoapBox\SignedRequests\Middlewares\Guzzle\GenerateSignature;
use SoapBox\AgendaTemplateClient\Exceptions\GoalNotFoundException;
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
     * Retrieve an agenda using slug or Id using agenda template API
     *
     * @param int $userId
     * @param string|int $slug
     *
     * @return \SoapBox\AgendaTemplateClient\RemoteResources\AgendaTemplate
     * @throws AgendaTemplateNotFoundException
     */
    public function getAgendaTemplateModel(int $userId, $slugOrId): AgendaTemplate
    {
        try {
            $response = $this->client->get("custom-templates/{$slugOrId}?soapbox-user-id={$userId}", ['json' => []]);
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
     * Retrieve an agenda using slug or Id using agenda template API
     *
     * @throws \GuzzleHttp\Exception\RequestException
     * Thrown if a response was not returned from the agenda template service
     *
     * @param int $userId
     * @param string|int $slug
     *
     * @return \Illuminate\Http\Response
     */
    public function getAgendaTemplate(int $userId, $slugOrId): Response
    {
        return $this->makeRequestAndReturnResponse("get", "custom-templates/{$slugOrId}?soapbox-user-id={$userId}", []);
    }

    /**
     * Retrieve an agenda templates based on the query string
     *
     * @throws \GuzzleHttp\Exception\RequestException
     * Thrown if a response was not returned from the agenda template service
     *
     * @param int $userId
     * @param string $queryString
     *
     * @return \Illuminate\Http\Response
     */
    public function getAgendaTemplates(int $userId, string $queryString = null): Response
    {
        $url = $queryString ? "custom-templates?{$queryString}&" : "custom-templates?";
        $url = $url . "soapbox-user-id={$userId}";

        return $this->makeRequestAndReturnResponse("get", "$url", []);
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
    public function deleteAgendaTemplate(int $userId, int $agendaTemplateId): Response
    {
        $data = ['soapbox-user-id' => $userId];

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

        return $this->makeRequestAndReturnResponse("put", "items/{$itemId}", $data);
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
    public function deleteAgendaTemplateItem(int $userId, int $itemId): Response
    {
        $data = ['soapbox-user-id' => $userId];

        return $this->makeRequestAndReturnResponse("delete", "items/{$itemId}", $data);
    }

    /**
     * Retrieve a suggested goal using the agenda template API and return the model
     *
     * @throws \GuzzleHttp\Exception\RequestException
     * Thrown if a response was not returned from the suggestion provider service
     *
     * @param int $suggestedGoalId
     * @param string $queryString
     *
     * @return \SoapBox\AgendaTemplateClient\RemoteResources\SuggestedGoal
     */
    public function getSuggestedGoalModel(int $suggestedGoalId, string $queryString = null): SuggestedGoal
    {
        $baseUrl = "suggested-goals/{$suggestedGoalId}";
        $url = $queryString ? $baseUrl . "?{$queryString}" : $baseUrl;

        $response = $this->makeRequestAndReturnResponse("get", "{$url}", []);

        if ($response->getStatusCode() != Response::HTTP_OK) {
            throw new GoalNotFoundException();
        }

        return (new Parser($response->getContent()))->getSuggestedGoal();
    }

    /**
     * Retrieve a suggested goal using the agenda template API
     *
     * @throws \GuzzleHttp\Exception\RequestException
     * Thrown if a response was not returned from the suggestion provider service
     *
     * @param int $suggestedGoalId
     * @param string $queryString
     *
     * @return \Illuminate\Http\Response
     */
    public function getSuggestedGoal(int $suggestedGoalId, string $queryString = null): Response
    {
        $baseUrl = "suggested-goals/{$suggestedGoalId}";
        $url = $queryString ? $baseUrl . "?{$queryString}" : $baseUrl;

        return $this->makeRequestAndReturnResponse("get", "{$url}", []);
    }

    /**
     * Retrieve suggested goals based on the query string
     *
     * @throws \GuzzleHttp\Exception\RequestException
     * Thrown if a response was not returned from the suggestion provider service
     *
     * @param int $departmentId
     * @param string $queryString
     *
     * @return \Illuminate\Http\Response
     */
    public function getSuggestedGoals(int $departmentId, string $queryString = null): Response
    {
        $baseUrl = "departments/{$departmentId}/suggested-goals";
        $url = $queryString ? $baseUrl . "?{$queryString}" : $baseUrl;

        return $this->makeRequestAndReturnResponse("get", "{$url}", []);
    }

    /**
     * Retrieve departments based on the query string
     *
     * @throws \GuzzleHttp\Exception\RequestException
     * Thrown if a response was not returned from the suggestion provider service
     *
     * @param string $queryString
     *
     * @return \Illuminate\Http\Response
     */
    public function getDepartments(string $queryString = null): Response
    {
        $baseUrl = "departments";
        $url = $queryString ? $baseUrl . "?{$queryString}" : $baseUrl;

        return $this->makeRequestAndReturnResponse("get", "{$url}", []);
    }
}
