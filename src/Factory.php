<?php

namespace SoapBox\AgendaTemplateClient;

use Illuminate\Support\Collection;
use Art4\JsonApiClient\V1\ResourceItem;
use SoapBox\AgendaTemplateClient\RemoteResources\Resource;
use SoapBox\AgendaTemplateClient\RemoteResources\AgendaItem;
use SoapBox\AgendaTemplateClient\RemoteResources\MeetingRatingQuestion;

class Factory
{
    private $map = [
        'agenda-items' => AgendaItem::class,
        'meeting-rating-questions' => MeetingRatingQuestion::class
    ];

    /**
     * Make the appropriate resource depending on whether it's a meeting rating question or an agenda item
     *
     * @return mixed
     */
    public function makeResource(string $type, ResourceItem $resourceItem, Collection $mappedIncluded)
    {

        $class = array_get($this->map, $type, Resource::class);
        return new $class($resourceItem, $mappedIncluded);

    }
}
