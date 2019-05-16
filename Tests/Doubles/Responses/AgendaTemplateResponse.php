<?php

namespace Tests\Doubles\Responses;

class AgendaTemplateResponse extends Response
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
                    "short-description" => "asad",
                    "long-description" => "dfgfdg",
                    "mascot" => "",
                    "background" => "",
                    "org-name" => "",
                    "org-logo" => "",
                    "created-at" => "",
                    "updated-at" => ""
                ],
                "relationships" => [
                    "meeting-rating-questions" => [
                        "data" => [
                            [
                                "type" => "meeting-rating-questions",
                                "id" => "1"
                            ]
                        ]
                    ],
                    "agenda-items" => [
                        "data" => [
                            [
                                "type" => "agenda-items",
                                "id" => "1"
                            ],
                            [
                                "type" => "agenda-items",
                                "id" => "2"
                            ]
                        ]
                    ]
                ]
            ],
            "included" => [
                [
                    "type" => "agenda-items",
                    "id" => "1",
                    "attributes" => [
                        "title" => "ddd",
                        "is-repeating" => true,
                        "created-at" => "2019-04-09 19:11:18",
                        "updated-at" => "2019-04-09 19:11:18"
                    ],

                ],
                [
                    "type" => "agenda-items",
                    "id" => "2",
                    "attributes" => [
                        "title" => "eee",
                        "is-repeating" => false,
                        "created-at" => "2018-04-09 19:11:18",
                        "updated-at" => "2018-04-09 19:11:18"
                    ]
                ],
                [
                    "type" => "meeting-rating-questions",
                    "id" => "1",
                    "attributes" => [
                        "question" => "How would you rate this meeting?",
                        "responses" => [
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
                        ],
                        "created-at" => "2019-04-09 19:11:18",
                        "updated-at" => "2019-04-09 19:11:18"
                    ]
                ]
            ]
        ];

        parent::__construct($data, 200);
    }
}
