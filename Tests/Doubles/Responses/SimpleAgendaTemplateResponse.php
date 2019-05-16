<?php

namespace Tests\Doubles\Responses;

class SimpleAgendaTemplateResponse extends Response
{
    /**
     * @var array
     */
    private $data;

    /**
     * Create a new Stripe Response object
     *
     * @param array $data
     * @param int $status
     */
    public function __construct()
    {
        $data = [
            "data" => [
                "type" => "agenda-templates",
                "id" => "1",
                "attributes" => [
                    "name" => "breh",
                    "channel-type" => "one-on-one",
                    "slug" => "scrum-meeting",
                    "short-description" => "more breh",
                    "long-description" => "even more breh",
                    "mascot" => ":pig:",
                    "background" => "red",
                    "org-name" => "breh",
                    "org-logo" => ":pig:",
                    "created-at" => "2019-04-09 19:11:18",
                    "updated-at" => "2019-04-09 19:11:18"
                ],
            ],
        ];

        parent::__construct($data, 200);
    }
}
