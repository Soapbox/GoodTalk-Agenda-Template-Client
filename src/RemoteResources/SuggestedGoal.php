<?php

namespace SoapBox\AgendaTemplateClient\RemoteResources;

class SuggestedGoal extends Resource
{
    /**
     * Leverage getRelationship to return all milestones for the goal
     *
     * @return Illuminate\Support\Collection;
     */
    public function getMilestones()
    {
        return $this->getRelationship('milestones');
    }
}
