<?php

namespace Tests\Feature;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Config;
use SoapBox\AgendaTemplateClient\Client;
use Tests\Doubles\Responses\SuggestedGoalResponse;
use JSHayes\FakeRequests\Traits\Laravel\FakeRequests;
use SoapBox\AgendaTemplateClient\RemoteResources\Milestone;
use SoapBox\AgendaTemplateClient\RemoteResources\SuggestedGoal;

class SuggestedGoalTest extends TestCase
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

    /**
     * @test
     */
    public function it_correctly_gets_a_suggested_goal_with_mile_stones()
    {
        $handler = $this->fakeRequests();
        $handler->get('suggested-goals/1?include=milestones')
            ->respondWith(new SuggestedGoalResponse());

        $client = resolve(Client::class);
        $suggestedGoal = $client->getSuggestedGoalModel(1, 'include=milestones');

        $this->assertInstanceOf(SuggestedGoal::class, $suggestedGoal);
        $this->assertEquals($suggestedGoal->getId(), 1);
        $this->assertEquals($suggestedGoal->title, 'Researching and Improving Customer Satisfaction');
        $this->assertEquals($suggestedGoal->description, 'Listen to the customers');
        $this->assertEquals($suggestedGoal->icon, ':bird:');
        $this->assertEquals($suggestedGoal->created_at, '2020-10-04 20:58:08');
        $this->assertEquals($suggestedGoal->updated_at, '2020-10-04 20:58:08');

        $milestones = $suggestedGoal->getMilestones();

        $this->assertEquals($milestones->count(), 3);

        $milestone3 = $milestones->pop();
        $milestone2 = $milestones->pop();
        $milestone1 = $milestones->pop();

        $this->assertInstanceOf(Milestone::class, $milestone1);
        $this->assertEquals($milestone1->getId(), 1);
        $this->assertEquals($milestone1->body, 'get 50 customers on board');
        $this->assertEquals($milestone1->created_at, '2020-10-04 20:58:09');
        $this->assertEquals($milestone1->updated_at, '2020-10-04 20:58:09');

        $this->assertInstanceOf(Milestone::class, $milestone2);
        $this->assertEquals($milestone2->getId(), 2);
        $this->assertEquals($milestone2->body, 'get 100 customers on board');
        $this->assertEquals($milestone2->created_at, '2020-10-04 20:58:10');
        $this->assertEquals($milestone2->updated_at, '2020-10-04 20:58:10');

        $this->assertInstanceOf(Milestone::class, $milestone3);
        $this->assertEquals($milestone3->getId(), 3);
        $this->assertEquals($milestone3->body, 'get 200 customers on board');
        $this->assertEquals($milestone3->created_at, '2020-10-04 20:58:11');
        $this->assertEquals($milestone3->updated_at, '2020-10-04 20:58:11');
    }
}
