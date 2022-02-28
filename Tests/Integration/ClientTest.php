<?php

namespace Tests\Integration;

use Illuminate\Http\Response;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Config;
use SoapBox\AgendaTemplateClient\Client;
use Tests\Doubles\Responses\SuggestedGoalResponse;
use Tests\Doubles\Responses\AgendaTemplateResponse;
use JSHayes\FakeRequests\Traits\Laravel\FakeRequests;
use SoapBox\AgendaTemplateClient\RemoteResources\SuggestedGoal;
use SoapBox\AgendaTemplateClient\RemoteResources\AgendaTemplate;
use Tests\Doubles\Responses\RecentlyAddedOrUpdatedItemsResponse;
use SoapBox\AgendaTemplateClient\Exceptions\GoalNotFoundException;
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
        $handler->get('custom-templates/scrum-meeting?soapbox-user-id=1&soapbox-id=2')
            ->respondWith(new AgendaTemplateResponse());

        $client = resolve(Client::class);
        $agendaTemplate = $client->getAgendaTemplateModel(1, 2, 'scrum-meeting');
        $this->assertInstanceOf(AgendaTemplate::class, $agendaTemplate);
    }

    /**
     * @test
     */
    public function it_throws_an_AgendaTemplateNotFoundException_error_when_the_agenda_template_does_not_exist()
    {
        $this->expectException(AgendaTemplateNotFoundException::class);

        $handler = $this->fakeRequests();
        $handler->get('custom-templates/idea?soapbox-user-id=1&soapbox-id=2')
            ->respondWith(404);

        $client = resolve(Client::class);
        $agendaTemplate = $client->getAgendaTemplateModel(1, 2, 'idea');
    }

    /**
     * @test
     */
    public function it_throws_an_AgendaTemplateNotFoundException_error_when_the_service_goes_down()
    {
        $this->expectException(AgendaTemplateNotFoundException::class);

        $handler = $this->fakeRequests();
        $handler->get('custom-templates/scrum-meeting?soapbox-user-id=1&soapbox-id=2')
            ->respondWith(500);

        $client = resolve(Client::class);
        $agendaTemplate = $client->getAgendaTemplateModel(1, 2, 'scrum-meeting');
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
        $handler->get('custom-templates/scrum-meeting?soapbox-user-id=1&soapbox-id=2')
            ->respondWith(new AgendaTemplateResponse());

        $client = resolve(Client::class);
        $response = $client->getAgendaTemplate(1, 2, 'scrum-meeting');

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
        $handler->get('custom-templates/idea?soapbox-user-id=1&soapbox-id=2')
            ->respondWith(422);

        $client = resolve(Client::class);
        $response = $client->getAgendaTemplate(1, 2, 'idea');
        $this->assertEquals($response->getStatusCode(), 422);
    }

    /**
     * @test
     */
    public function it_can_fetch_agenda_templates_without_query_params()
    {
        $handler = $this->fakeRequests();
        $handler->get('custom-templates?soapbox-user-id=1&soapbox-id=2')
            ->respondWith(200);

        $client = resolve(Client::class);
        $response = $client->getAgendaTemplates(1, 2);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @test
     */
    public function it_can_fetch_agenda_templates_with_the_correct_query_params()
    {
        $handler = $this->fakeRequests();
        $handler->get('custom-templates?filter%5Btype%5D=one-on-one&filter%5Bvisibility%5D=public&soapbox-user-id=1&soapbox-id=2')
            ->respondWith(200);

        $client = resolve(Client::class);
        $response = $client->getAgendaTemplates(1, 2, 'filter[type]=one-on-one&filter[visibility]=public');

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @test
     */
    public function any_error_response_is_returned_when_fetching_agenda_templates()
    {
        $handler = $this->fakeRequests();
        $handler->get('custom-templates?soapbox-user-id=1&soapbox-id=2')
            ->respondWith(422);

        $client = resolve(Client::class);
        $response = $client->getAgendaTemplates(1, 2);
        $this->assertEquals($response->getStatusCode(), 422);
    }

    /**
     * @test
     */
    public function it_can_create_an_agenda_template_with_the_correct_data()
    {
        $userData = [
            'name' => 'name',
            'email' => 'name@email.com',
            'avatar' => 'https://avatar.com',
            'soapbox-user-id' => 1,
        ];

        $handler = $this->fakeRequests();
        $handler->post('agenda-templates')
            ->inspectRequest(function ($request) {
                $content = json_decode((string) $request->getBody(), true);
                $this->assertEquals($content['author-name'], $userData['name']);
                $this->assertEquals($content['author-email'], $userData['email']);
                $this->assertEquals($content['author-avatar'], $userData['avatar']);
                $this->assertEquals($content['soapbox-user-id'], $userData['soapbox-user-id']);
                $this->assertEquals($content['soapbox-id'], 10);
                $this->assertEquals($content['test'], 'test data');
            })->respondWith(200);

        $client = resolve(Client::class);
        $response = $client->createAgendaTemplate($userData, 10, ['test' => 'test data']);

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
        $userData = [
            'name' => 'name',
            'email' => 'name@email.com',
            'avatar' => 'https://avatar.com',
            'soapbox-user-id' => 1,
        ];

        $handler = $this->fakeRequests();
        $handler->put('agenda-templates/2')
            ->inspectRequest(function ($request) {
                $content = json_decode((string) $request->getBody(), true);
                $this->assertEquals($content['author-name'], $userData['name']);
                $this->assertEquals($content['author-email'], $userData['email']);
                $this->assertEquals($content['author-avatar'], $userData['avatar']);
                $this->assertEquals($content['soapbox-user-id'], $userData['soapbox-user-id']);
                $this->assertEquals($content['test'], 'test data');
            })->respondWith(200);

        $client = resolve(Client::class);
        $response = $client->updateAgendaTemplate($userData, 2, ['test' => 'test data']);

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

    /**
     * @test
     */
    public function it_can_get_a_suggested_goal_response()
    {
        $handler = $this->fakeRequests();
        $handler->get('suggested-goals/1')
            ->respondWith(new SuggestedGoalResponse());

        $client = resolve(Client::class);
        $response = $client->getSuggestedGoal(1);

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($this->getResponseData($response)['id'], 1);
        $this->assertEquals($this->getResponseData($response)['type'], 'suggested-goal');
    }

    /**
     * @test
     */
    public function it_can_get_a_suggested_goal_with_query_params()
    {
        $handler = $this->fakeRequests();
        $handler->get('suggested-goals/1?include=milestones')
            ->respondWith(new SuggestedGoalResponse());

        $client = resolve(Client::class);
        $response = $client->getSuggestedGoal(1, 'include=milestones');

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($this->getResponseData($response)['id'], 1);
        $this->assertEquals($this->getResponseData($response)['type'], 'suggested-goal');
    }

    /**
     * @test
     */
    public function any_error_response_is_returned_when_fetching_a_suggested_goal()
    {
        $handler = $this->fakeRequests();
        $handler->get('suggested-goals/1')
            ->respondWith(422);

        $client = resolve(Client::class);
        $response = $client->getSuggestedGoal(1);
        $this->assertEquals($response->getStatusCode(), 422);
    }

    /**
     * @test
     */
    public function it_can_get_a_suggested_goal_model()
    {
        $handler = $this->fakeRequests();
        $handler->get('suggested-goals/1')
            ->respondWith(new SuggestedGoalResponse());

        $client = resolve(Client::class);
        $suggestedGoal = $client->getSuggestedGoalModel(1);

        $this->assertInstanceOf(SuggestedGoal::class, $suggestedGoal);
    }

    /**
     * @test
     */
    public function it_can_get_a_suggested_goal_model_with_query_params()
    {
        $handler = $this->fakeRequests();
        $handler->get('suggested-goals/1?include=milestones')
            ->respondWith(new SuggestedGoalResponse());

        $client = resolve(Client::class);
        $suggestedGoal = $client->getSuggestedGoalModel(1, 'include=milestones');

        $this->assertInstanceOf(SuggestedGoal::class, $suggestedGoal);
    }

    /**
     * @test
     */
    public function a_goal_not_found_exception_is_thrown_when_an_error_is_returned_when_fetching_a_suggested_goal_model()
    {
        $this->expectException(GoalNotFoundException::class);
        $handler = $this->fakeRequests();
        $handler->get('suggested-goals/1')
            ->respondWith(404);

        $client = resolve(Client::class);
        $client->getSuggestedGoalModel(1);
    }

    /**
     * @test
     */
    public function it_can_fetch_goals_in_a_department()
    {
        $handler = $this->fakeRequests();
        $handler->get('departments/1/suggested-goals')
            ->respondWith(200);

        $client = resolve(Client::class);
        $response = $client->getSuggestedGoals(1);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @test
     */
    public function it_can_fetch_goals_in_a_department_with_query_params()
    {
        $handler = $this->fakeRequests();
        $handler->get('departments/1/suggested-goals?include=milestones')
            ->respondWith(200);

        $client = resolve(Client::class);
        $response = $client->getSuggestedGoals(1, 'include=milestones');

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @test
     */
    public function any_error_response_is_returned_when_fetching_a_suggested_goals_in_a_department()
    {
        $handler = $this->fakeRequests();
        $handler->get('departments/1/suggested-goals')
            ->respondWith(422);

        $client = resolve(Client::class);
        $response = $client->getSuggestedGoals(1);
        $this->assertEquals($response->getStatusCode(), 422);
    }

    /**
     * @test
     */
    public function it_can_fetch_departments()
    {
        $handler = $this->fakeRequests();
        $handler->get('departments')
            ->respondWith(200);

        $client = resolve(Client::class);
        $response = $client->getDepartments();

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @test
     */
    public function it_can_fetch_departments_with_query_params()
    {
        $handler = $this->fakeRequests();
        $handler->get('departments?include=milestones')
            ->respondWith(200);

        $client = resolve(Client::class);
        $response = $client->getDepartments('include=milestones');

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @test
     */
    public function any_error_response_is_returned_when_fetching_departments()
    {
        $handler = $this->fakeRequests();
        $handler->get('departments')
            ->respondWith(422);

        $client = resolve(Client::class);
        $response = $client->getDepartments();
        $this->assertEquals($response->getStatusCode(), 422);
    }

    /**
     * @test
     */
    public function it_can_create_an_agenda_template_section_with_the_correct_data()
    {
        $handler = $this->fakeRequests();
        $handler->post('agenda-templates/2/sections')
            ->inspectRequest(function ($request) {
                $content = json_decode((string) $request->getBody(), true);
                $this->assertEquals($content['soapbox-user-id'], 1);
                $this->assertEquals($content['test'], 'test data');
            })->respondWith(200);

        $client = resolve(Client::class);
        $response = $client->createAgendaTemplateSection(1, 2, ['test' => 'test data']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @test
     */
    public function any_error_response_is_returned_when_creating_an_agenda_template_section()
    {
        $handler = $this->fakeRequests();
        $handler->post('agenda-templates/2/sections')
            ->respondWith(422);

        $client = resolve(Client::class);
        $response = $client->createAgendaTemplateSection(1, 2, []);
        $this->assertEquals($response->getStatusCode(), 422);
    }

    /**
     * @test
     */
    public function it_can_update_an_agenda_template_section_with_the_correct_data()
    {
        $handler = $this->fakeRequests();
        $handler->put('sections/2')
            ->inspectRequest(function ($request) {
                $content = json_decode((string) $request->getBody(), true);
                $this->assertEquals($content['soapbox-user-id'], 1);
                $this->assertEquals($content['test'], 'test data');
            })->respondWith(200);

        $client = resolve(Client::class);
        $response = $client->updateAgendaTemplateSection(1, 2, ['test' => 'test data']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @test
     */
    public function any_error_response_is_returned_when_updating_an_agenda_template_section()
    {
        $handler = $this->fakeRequests();
        $handler->put('sections/2')
            ->respondWith(422);

        $client = resolve(Client::class);
        $response = $client->updateAgendaTemplateSection(1, 2, []);
        $this->assertEquals($response->getStatusCode(), 422);
    }

    /**
     * @test
     */
    public function it_can_delete_an_agenda_template_section()
    {
        $handler = $this->fakeRequests();
        $handler->delete('sections/2')
            ->inspectRequest(function ($request) {
                $content = json_decode((string) $request->getBody(), true);
                $this->assertEquals($content['soapbox-user-id'], 1);
            })->respondWith(204);

        $client = resolve(Client::class);
        $response = $client->deleteAgendaTemplateSection(1, 2);

        $this->assertEquals($response->getStatusCode(), 204);
    }

    /**
     * @test
     */
    public function any_error_response_is_returned_when_deleting_an_agenda_template_section()
    {
        $handler = $this->fakeRequests();
        $handler->delete('sections/2')
            ->respondWith(422);

        $client = resolve(Client::class);
        $response = $client->deleteAgendaTemplateSection(1, 2);
        $this->assertEquals($response->getStatusCode(), 422);
    }
}
