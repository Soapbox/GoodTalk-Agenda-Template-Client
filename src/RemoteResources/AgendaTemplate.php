<?php

namespace SoapBox\AgendaTemplateClient\RemoteResources;

class AgendaTemplate extends Resource
{
    /**
     * Leverage getRelationship to return all agenda items for the Agenda Template
     *
     * @return Illuminate\Support\Collection;
     */
    public function getAgendaItems()
    {
        return $this->getRelationship('agenda-items');
    }

    /**
     * Leverage getRelationship to return the meeting rating question for the Agenda Template
     *
     * @return Illuminate\Support\Collection;
     */
    public function getMeetingRatingQuestion()
    {
        return $this->getRelationship('meeting-rating-questions')->first();
    }
}
