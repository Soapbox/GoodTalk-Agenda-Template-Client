<?php

namespace Tests\Feature;

use Orchestra\Testbench\TestCase;
use SoapBox\AgendaTemplateClient\Client;
use Tests\Doubles\Responses\AgendaTemplateResponse;
use JSHayes\FakeRequests\Traits\Laravel\FakeRequests;
use Tests\Doubles\Responses\SimpleAgendaTemplateResponse;
use SoapBox\AgendaTemplateClient\RemoteResources\AgendaItem;
use SoapBox\AgendaTemplateClient\RemoteResources\AgendaTemplate;
use SoapBox\AgendaTemplateClient\RemoteResources\MeetingRatingQuestion;

class AgendaTemplateTest extends TestCase
{
    use FakeRequests;

    private function assertAgendaTemplateAttributes(AgendaTemplate $agendaTemplate)
    {
        $this->assertEquals($agendaTemplate->getId(), 1);
        $this->assertEquals($agendaTemplate->channel_type, 'one-on-one');
        $this->assertEquals($agendaTemplate->short_description, 'more breh');
        $this->assertEquals($agendaTemplate->long_description, 'even more breh');
        $this->assertEquals($agendaTemplate->mascot, ':pig:');
        $this->assertEquals($agendaTemplate->background, 'red');
        $this->assertEquals($agendaTemplate->org_name, 'breh');
        $this->assertEquals($agendaTemplate->org_logo, ':pig:');
        $this->assertEquals($agendaTemplate->created_at, '2019-04-09 19:11:18');
        $this->assertEquals($agendaTemplate->updated_at, '2019-04-09 19:11:18');
    }
    /**
     * @test
     */
    public function it_correctly_gets_an_agenda_template_with_no_meeting_ratings_question_or_items()
    {
        $response = new SimpleAgendaTemplateResponse();

        $handler = $this->fakeRequests();
        $handler->get('agenda-templates/1')
            ->respondWith($response);

        $client = resolve(Client::class);
        $agendaTemplate = $client->getAgendaTemplate(1);

        $this->assertInstanceOf(AgendaTemplate::class, $agendaTemplate);

        $this->assertAgendaTemplateAttributes($agendaTemplate);

        $this->assertEquals($agendaTemplate->getMeetingRatingQuestion(), null);
        $this->assertEquals($agendaTemplate->getAgendaItems(), collect([]));
    }

    /**
     * @test
     */
    public function it_correctly_gets_an_agenda_template_with_a_meeting_ratings_question()
    {
        $response = new AgendaTemplateResponse();

        $handler = $this->fakeRequests();
        $handler->get('agenda-templates/1')
            ->respondWith($response);

        $client = resolve(Client::class);
        $agendaTemplate = $client->getAgendaTemplate(1);

        $meetingRatingQuestion = $agendaTemplate->getMeetingRatingQuestion();

        $this->assertInstanceOf(MeetingRatingQuestion::class, $meetingRatingQuestion);

        $this->assertEquals($meetingRatingQuestion->getId(), 1);
        $this->assertEquals($meetingRatingQuestion->question, 'How would you rate this meeting?');
        $this->assertEquals(
            $meetingRatingQuestion->getResponses(),
            [
                [
                    "emoji" => ":smile:",
                    "text" => "Excellent"
                ],
                [
                    "emoji" => ":slightly_smiling_face:",
                    "text" => "Good"
                ],
                [
                    "emoji" => ":neutral_face:",
                    "text" => "Needs improvement"
                ]
            ]
        );
        $this->assertEquals($meetingRatingQuestion->created_at, '2019-04-09 19:11:18');
        $this->assertEquals($meetingRatingQuestion->updated_at, '2019-04-09 19:11:18');
    }

    /**
     * @test
     */
    public function it_correctly_gets_an_agenda_template_with_agenda_items()
    {
        $response = new AgendaTemplateResponse();

        $handler = $this->fakeRequests();
        $handler->get('agenda-templates/1')
            ->respondWith($response);

        $client = resolve(Client::class);
        $agendaTemplate = $client->getAgendaTemplate(1);

        $items = $agendaTemplate->getAgendaItems();

        $this->assertEquals($agendaTemplate->getAgendaItems()->count(), 2);

        $agendaItem2 = $items->pop();
        $agendaItem1 = $items->pop();

        $this->assertInstanceOf(AgendaItem::class, $agendaItem1);
        $this->assertEquals($agendaItem1->getId(), 1);
        $this->assertEquals($agendaItem1->title, 'ddd');
        $this->assertEquals($agendaItem1->is_repeating, true);
        $this->assertEquals($agendaItem1->created_at, '2019-04-09 19:11:18');
        $this->assertEquals($agendaItem1->updated_at, '2019-04-09 19:11:18');

        $this->assertInstanceOf(AgendaItem::class, $agendaItem2);
        $this->assertEquals($agendaItem2->getId(), 2);
        $this->assertEquals($agendaItem2->title, 'eee');
        $this->assertEquals($agendaItem2->is_repeating, false);
        $this->assertEquals($agendaItem2->created_at, '2018-04-09 19:11:18');
        $this->assertEquals($agendaItem2->updated_at, '2018-04-09 19:11:18');
    }
}