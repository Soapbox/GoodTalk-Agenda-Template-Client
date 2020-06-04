<?php

namespace Tests\Integration;

use Illuminate\Http\Response;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Config;
use SoapBox\AgendaTemplateClient\Client;
use Tests\Doubles\Responses\AgendaTemplateResponse;
use JSHayes\FakeRequests\Traits\Laravel\FakeRequests;
use SoapBox\AgendaTemplateClient\RemoteResources\AgendaTemplate;
use Tests\Doubles\Responses\RecentlyAddedOrUpdatedItemsResponse;
use SoapBox\AgendaTemplateClient\Exceptions\ItemNotFoundException;
use SoapBox\AgendaTemplateClient\Exceptions\AgendaTemplateNotFoundException;

class ClientTest extends TestCase
{
    use FakeRequests;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setup();
        $configValues = require resource_path("../../../../../config/agenda-template-client.php");
        Config::set('agenda-template-client', $configValues);
    }

    private function getResponseData(Response $response)
    {
        return json_decode($response->getContent(), true)['data'];
    }

    /**
     * @test
     */
    public function it_can_get_an_agenda_template()
    {
        $handler = $this->fakeRequests();
        $handler->get('custom-templates/scrum-meeting')
            ->inspectRequest(function ($request) {
                $content = json_decode((string) $request->getBody(), true);
                $this->assertEquals($content['soapbox-user-id'], 1);
            })->respondWith(new AgendaTemplateResponse());

        $client = resolve(Client::class);
        $agendaTemplate = $client->getAgendaTemplateModel(1, 'scrum-meeting');
        $this->assertInstanceOf(AgendaTemplate::class, $agendaTemplate);
    }

    /**
     * @test
     */
    public function it_throws_an_AgendaTemplateNotFoundException_error_when_the_agenda_template_does_not_exist()
    {
        $this->expectException(AgendaTemplateNotFoundException::class);

        $handler = $this->fakeRequests();
        $handler->get('custom-templates/idea')
            ->respondWith(404);

        $client = resolve(Client::class);
        $agendaTemplate = $client->getAgendaTemplateModel(1, 'idea');
    }

    /**
     * @test
     */
    public function it_throws_an_AgendaTemplateNotFoundException_error_when_the_service_goes_down()
    {
        $this->expectException(AgendaTemplateNotFoundException::class);

        $handler = $this->fakeRequests();
        $handler->get('custom-templates/scrum-meeting')
            ->respondWith(500);

        $client = resolve(Client::class);
        $agendaTemplate = $client->getAgendaTemplateModel(1, 'scrum-meeting');
    }

    /**
     * @test
     */
    public function it_throws_an_ItemNotFoundException_when_no_items_found()
    {
        $this->expectException(ItemNotFoundException::class);

        $date = '2019-01-01 00:00:00';

        $handler = $this->fakeRequests();
        $handler->get(sprintf('items?date=%s', $date))
            ->respondWith(404);

        $client = resolve(Client::class);
        $items = $client->getRecentlyAddedOrUpdatedItems($date);
    }

    /**
     * @test
     */
    public function it_throws_an_ItemNotFoundException_when_service_down()
    {
        $this->expectException(ItemNotFoundException::class);

        $date = '2019-01-01 00:00:00';

        $handler = $this->fakeRequests();
        $handler->get(sprintf('items?date=%s', $date))
            ->respondWith(500);

        $client = resolve(Client::class);
        $items = $client->getRecentlyAddedOrUpdatedItems($date);
    }

    /**
     * @test
     */
    public function it_retrieves_a_list_of_recently_updated_or_added_items()
    {
        $date = '2019-01-01 00:00:00';

        $handler = $this->fakeRequests();
        $handler->get(sprintf('items?date=%s', $date))
            ->respondWith(new RecentlyAddedOrUpdatedItemsResponse());

        $client = resolve(Client::class);
        $items = $client->getRecentlyAddedOrUpdatedItems($date);

        $this->assertSame(3, count($items));

        //check ids in response match with expected response
        $ids = array_column($items, 'id');
        $this->assertEquals(in_array(1, $ids), true);
        $this->assertEquals(in_array(51, $ids), true);
        $this->assertEquals(in_array(52, $ids), true);
    }

    /**
     * @test
     */
    public function it_can_get_an_agenda_template_response()
    {
        $handler = $this->fakeRequests();
        $handler->get('custom-templates/scrum-meeting')
            ->inspectRequest(function ($request) {
                $content = json_decode((string) $request->getBody(), true);
                $this->assertEquals($content['soapbox-user-id'], 1);
            })->respondWith(new AgendaTemplateResponse());

        $client = resolve(Client::class);
        $response = $client->getAgendaTemplate(1, 'scrum-meeting');

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($this->getResponseData($response)['id'], 1);
        $this->assertEquals($this->getResponseData($response)['type'], 'agenda-templates');
    }

    /**
     * @test
     */
    public function any_error_response_is_returned_when_fetching_a_template()
    {
        $handler = $this->fakeRequests();
        $handler->get('custom-templates/idea')
            ->respondWith(422);

        $client = resolve(Client::class);
        $response = $client->getAgendaTemplate(1, 'idea');
        $this->assertEquals($response->getStatusCode(), 422);
    }

    /**
     * @test
     */
    public function it_can_fetch_agenda_templates_without_query_params()
    {
        $handler = $this->fakeRequests();
        $handler->get('custom-templates')
            ->inspectRequest(function ($request) {
                $content = json_decode((string) $request->getBody(), true);
                $this->assertEquals($content['soapbox-user-id'], 1);
            })->respondWith(200);

        $client = resolve(Client::class);
        $response = $client->getAgendaTemplates(1);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @test
     */
    public function it_can_fetch_agenda_templates_with_the_correct_query_params()
    {
        $handler = $this->fakeRequests();
        $handler->get('custom-templates?filter%5Btype%5D=one-on-one&filter%5Bvisibility%5D=public')
            ->inspectRequest(function ($request) {
                $content = json_decode((string) $request->getBody(), true);
                $this->assertEquals($content['soapbox-user-id'], 1);
            })->respondWith(200);

        $client = resolve(Client::class);
        $response = $client->getAgendaTemplates(1, 'filter[type]=one-on-one&filter[visibility]=public');

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @test
     */
    public function any_error_response_is_returned_when_fetching_agenda_templates()
    {
        $handler = $this->fakeRequests();
        $handler->get('custom-templates')
            ->respondWith(422);

        $client = resolve(Client::class);
        $response = $client->getAgendaTemplates(1);
        $this->assertEquals($response->getStatusCode(), 422);
    }

    /**
     * @test
     */
    public function it_can_create_an_agenda_template_with_the_correct_data()
    {
        $handler = $this->fakeRequests();
        $handler->post('agenda-templates')
            ->inspectRequest(function ($request) {
                $content = json_decode((string) $request->getBody(), true);
                $this->assertEquals($content['soapbox-user-id'], 1);
                $this->assertEquals($content['soapbox-id'], 10);
                $this->assertEquals($content['test'], 'test data');
            })->respondWith(200);

        $client = resolve(Client::class);
        $response = $client->createAgendaTemplate(1, 10, ['test' => 'test data']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @test
     */
    public function any_error_response_is_returned_when_creating_an_agenda_template()
    {
        $handler = $this->fakeRequests();
        $handler->post('agenda-templates')
            ->respondWith(422);

        $client = resolve(Client::class);
        $response = $client->createAgendaTemplate(1, 10, []);
        $this->assertEquals($response->getStatusCode(), 422);
    }

    /**
     * @test
     */
    public function it_can_update_an_agenda_template_with_the_correct_data()
    {
        $handler = $this->fakeRequests();
        $handler->put('agenda-templates/2')
            ->inspectRequest(function ($request) {
                $content = json_decode((string) $request->getBody(), true);
                $this->assertEquals($content['soapbox-user-id'], 1);
                $this->assertEquals($content['test'], 'test data');
            })->respondWith(200);

        $client = resolve(Client::class);
        $response = $client->updateAgendaTemplate(1, 2, ['test' => 'test data']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @test
     */
    public function any_error_response_is_returned_when_updating_an_agenda_template()
    {
        $handler = $this->fakeRequests();
        $handler->put('agenda-templates/2')
            ->respondWith(422);

        $client = resolve(Client::class);
        $response = $client->updateAgendaTemplate(1, 2, []);
        $this->assertEquals($response->getStatusCode(), 422);
    }

    /**
     * @test
     */
    public function it_can_delete_an_agenda_template()
    {
        $handler = $this->fakeRequests();
        $handler->delete('agenda-templates/2')
            ->inspectRequest(function ($request) {
                $content = json_decode((string) $request->getBody(), true);
                $this->assertEquals($content['soapbox-user-id'], 1);
            })->respondWith(204);

        $client = resolve(Client::class);
        $response = $client->deleteAgendaTemplate(1, 2);

        $this->assertEquals($response->getStatusCode(), 204);
    }

    /**
     * @test
     */
    public function any_error_response_is_returned_when_deleting_an_agenda_template()
    {
        $handler = $this->fakeRequests();
        $handler->delete('agenda-templates/2')
            ->respondWith(422);

        $client = resolve(Client::class);
        $response = $client->deleteAgendaTemplate(1, 2);
        $this->assertEquals($response->getStatusCode(), 422);
    }

    /**
     * @test
     */
    public function it_can_create_an_agenda_template_item_with_the_correct_data()
    {
        $handler = $this->fakeRequests();
        $handler->post('agenda-templates/2/items')
            ->inspectRequest(function ($request) {
                $content = json_decode((string) $request->getBody(), true);
                $this->assertEquals($content['soapbox-user-id'], 1);
                $this->assertEquals($content['test'], 'test data');
            })->respondWith(200);

        $client = resolve(Client::class);
        $response = $client->createAgendaTemplateItem(1, 2, ['test' => 'test data']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @test
     */
    public function any_error_response_is_returned_when_creating_an_agenda_template_item()
    {
        $handler = $this->fakeRequests();
        $handler->post('agenda-templates/2/items')
            ->respondWith(422);

        $client = resolve(Client::class);
        $response = $client->createAgendaTemplateItem(1, 2, []);
        $this->assertEquals($response->getStatusCode(), 422);
    }

    /**
     * @test
     */
    public function it_can_update_an_agenda_template_item_with_the_correct_data()
    {
        $handler = $this->fakeRequests();
        $handler->put('items/2')
            ->inspectRequest(function ($request) {
                $content = json_decode((string) $request->getBody(), true);
                $this->assertEquals($content['soapbox-user-id'], 1);
                $this->assertEquals($content['test'], 'test data');
            })->respondWith(200);

        $client = resolve(Client::class);
        $response = $client->updateAgendaTemplateItem(1, 2, ['test' => 'test data']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @test
     */
    public function any_error_response_is_returned_when_updating_an_agenda_template_item()
    {
        $handler = $this->fakeRequests();
        $handler->put('items/2')
            ->respondWith(422);

        $client = resolve(Client::class);
        $response = $client->updateAgendaTemplateItem(1, 2, []);
        $this->assertEquals($response->getStatusCode(), 422);
    }

    /**
     * @test
     */
    public function it_can_delete_an_agenda_template_item()
    {
        $handler = $this->fakeRequests();
        $handler->delete('items/2')
            ->inspectRequest(function ($request) {
                $content = json_decode((string) $request->getBody(), true);
                $this->assertEquals($content['soapbox-user-id'], 1);
            })->respondWith(204);

        $client = resolve(Client::class);
        $response = $client->deleteAgendaTemplateItem(1, 2);

        $this->assertEquals($response->getStatusCode(), 204);
    }

    /**
     * @test
     */
    public function any_error_response_is_returned_when_deleting_an_agenda_template_item()
    {
        $handler = $this->fakeRequests();
        $handler->delete('items/2')
            ->respondWith(422);

        $client = resolve(Client::class);
        $response = $client->deleteAgendaTemplateItem(1, 2);
        $this->assertEquals($response->getStatusCode(), 422);
    }
}
