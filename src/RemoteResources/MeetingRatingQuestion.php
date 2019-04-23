<?php

namespace SoapBox\AgendaTemplateClient\RemoteResources;

class MeetingRatingQuestion extends Resource
{
    /**
     * Returns an array of all the question responses
     *
     * @return array
     */
    public function getResponses(): array
    {
        // This is first encoded and then decoded to stay consistent with Laravel's model's array casting.
        return json_decode(json_encode($this->responses), true);
    }
}