<?php

namespace Tests\Doubles\Responses;

class SuggestedGoalResponse extends Response
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
                "type" => "suggested-goal",
                "id" => "1",
                "attributes" => [
                    "title" => "Researching and Improving Customer Satisfaction",
                    "description" => "Listen to the customers",
                    "icon" => ":bird:",
                    "user-id" => 1,
                    "created-at" => "2020-10-04 20:58:08",
                    "updated-at" => "2020-10-04 20:58:08",
                ],
                "relationships" => [
                    "milestones" => [
                        "data" => [
                            [
                                "type" => "milestone",
                                "id" => "1",
                            ],
                            [
                                "type" => "milestone",
                                "id" => "2",
                            ],
                            [
                                "type" => "milestone",
                                "id" => "3",
                            ],
                        ],
                    ],
                ],
            ],
            "included" => [
                [
                    "type" => "milestone",
                    "id" => "1",
                    "attributes" => [
                        "body" => "get 50 customers on board",
                        "suggested-goal-id" => "1",
                        "created-at" => "2020-10-04 20:58:09",
                        "updated-at" => "2020-10-04 20:58:09",
                    ],
                ],
                [
                    "type" => "milestone",
                    "id" => "2",
                    "attributes" => [
                        "body" => "get 100 customers on board",
                        "suggested-goal-id" => "1",
                        "created-at" => "2020-10-04 20:58:10",
                        "updated-at" => "2020-10-04 20:58:10",
                    ],
                ],
                [
                    "type" => "milestone",
                    "id" => "3",
                    "attributes" => [
                        "body" => "get 200 customers on board",
                        "suggested-goal-id" => "1",
                        "created-at" => "2020-10-04 20:58:11",
                        "updated-at" => "2020-10-04 20:58:11",
                    ],
                ],
            ],
        ];

        parent::__construct($data, 200);
    }
}
