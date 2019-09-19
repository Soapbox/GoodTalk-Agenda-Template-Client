<?php

namespace Tests\Integration;

use Orchestra\Testbench\TestCase;
use SoapBox\AgendaTemplateClient\Client;
use Tests\Doubles\Responses\AgendaTemplateResponse;
use JSHayes\FakeRequests\Traits\Laravel\FakeRequests;
use Tests\Doubles\Responses\RecentlyAddedOrUpdatedItemsResponse;
use SoapBox\AgendaTemplateClient\RemoteResources\AgendaTemplate;
use SoapBox\AgendaTemplateClient\Exceptions\ItemNotFoundException;
use SoapBox\AgendaTemplateClient\Exceptions\AgendaTemplateNotFoundException;

class ClientTest extends TestCase
{
    use FakeRequests;

    /**
     * @test
     */
    public function it_can_get_an_agenda_template()
    {
        $handler = $this->fakeRequests();
        $handler->get('agenda-templates/scrum-meeting')
            ->respondWith(new AgendaTemplateResponse());

        $client = resolve(Client::class);
        $agendaTemplate = $client->getAgendaTemplate('scrum-meeting');
        $this->assertInstanceOf(AgendaTemplate::class, $agendaTemplate);
    }

    /**
     * @test
     */
    public function it_throws_an_AgendaTemplateNotFoundException_error_when_the_agenda_template_does_not_exist()
    {
        $this->expectException(AgendaTemplateNotFoundException::class);

        $handler = $this->fakeRequests();
        $handler->get('agenda-templates/idea')
            ->respondWith(404);

        $client = resolve(Client::class);
        $agendaTemplate = $client->getAgendaTemplate('idea');
    }

    /**
     * @test
     */
    public function it_throws_an_AgendaTemplateNotFoundException_error_when_the_service_goes_down()
    {
        $this->expectException(AgendaTemplateNotFoundException::class);

        $handler = $this->fakeRequests();
        $handler->get('agenda-templates/scrum-meeting')
            ->respondWith(500);

        $client = resolve(Client::class);
        $agendaTemplate = $client->getAgendaTemplate('scrum-meeting');
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
}
