<?php

namespace Tests\Integration;

use Orchestra\Testbench\TestCase;
use SoapBox\AgendaTemplateClient\Client;
use Tests\Doubles\Responses\AgendaTemplateResponse;
use JSHayes\FakeRequests\Traits\Laravel\FakeRequests;
use SoapBox\AgendaTemplateClient\RemoteResources\AgendaTemplate;
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
        $handler->get('agenda-templates.services.soapboxdev.com/api/agenda-templates/1')
            ->respondWith(new AgendaTemplateResponse());

        $client = resolve(Client::class);
        $agendaTemplate = $client->getAgendaTemplate(1);
        $this->assertInstanceOf(AgendaTemplate::class, $agendaTemplate);
    }

    /**
     * @test
     */
    public function it_throws_an_AgendaTemplateNotFoundException_error_when_the_agenda_template_does_not_exist()
    {
        $this->expectException(AgendaTemplateNotFoundException::class);

        $handler = $this->fakeRequests();
        $handler->get('agenda-templates.services.soapboxdev.com/api/agenda-templates/99')
            ->respondWith(404);

        $client = resolve(Client::class);
        $agendaTemplate = $client->getAgendaTemplate(99);
    }

    /**
     * @test
     */
    public function it_throws_an_AgendaTemplateNotFoundException_error_when_the_service_goes_down()
    {
        $this->expectException(AgendaTemplateNotFoundException::class);

        $handler = $this->fakeRequests();
        $handler->get('agenda-templates.services.soapboxdev.com/api/agenda-templates/99')
            ->respondWith(500);

        $client = resolve(Client::class);
        $agendaTemplate = $client->getAgendaTemplate(99);
    }
}