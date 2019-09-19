<?php

namespace Tests\Doubles\Responses;

class RecentlyAddedOrUpdatedItemsResponse extends Response
{
    public $data;

    /**
     * Create a new getRecentlyAddedOrUpdatedItems Response
     */
    public function __construct()
    {
        $data = [
            0 => [
                "id" => 1,
                "name" => "Sales Team Meeting",
                "type" => "group",
                "title" => "Successes and wins (5 min)",
            ],
            1 => [
                "id" => 51,
                "name" => "Sales Team Meeting",
                "type" => "group",
                "title" => "Test Again",
            ],
            2 => [
                "id" => 52,
                "name" => "Sales Team Meeting",
                "type" => "group",
                "title" => "🙋‍♀️ Personal updates yes",
            ],
        ];

        parent::__construct($data, 200);
    }
}
