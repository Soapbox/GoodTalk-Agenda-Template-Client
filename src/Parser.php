<?php

namespace SoapBox\AgendaTemplateClient;

use Illuminate\Support\Collection;
use Art4\JsonApiClient\Helper\Parser as JsonParser;
use SoapBox\AgendaTemplateClient\RemoteResources\SuggestedGoal;
use SoapBox\AgendaTemplateClient\RemoteResources\AgendaTemplate;

class Parser
{
    private $document;
    private $mappedIncludes;

    public function __construct(string $json)
    {
        $this->document = JsonParser::parseResponseString($json);
        $this->factory = new Factory();
    }

    /**
     * Return the Agenda Template
     *
     * @return SoapBox\AgendaTemplateClient\RemoteResources\AgendaTemplate
     */
    public function getAgendaTemplate(): AgendaTemplate
    {
        return new AgendaTemplate($this->document->get('data'), $this->getMappedIncluded());
    }

    /**
     * Return the goal
     *
     * @return SoapBox\AgendaTemplateClient\RemoteResources\SuggestedGoal
     */
    public function getSuggestedGoal(): SuggestedGoal
    {
        return new SuggestedGoal($this->document->get('data'), $this->getMappedIncluded());
    }

    /**
     * Create the mapping of each resource item to its included attributes
     *
     * @return Illuminate\Support\Collection
     */
    private function getMappedIncluded(): Collection
    {
        if (!$this->mappedIncludes) {
            $this->mappedIncludes = new Collection();
            if ($this->document->has('included')) {
                foreach ($this->document->get('included')->getKeys() as $index) {
                    $item = $this->document->get('included')->get($index);
                    $this->mappedIncludes[$item->get('type') . '-' . $item->get('id')] = $this->factory->makeResource($item->get('type'), $item, $this->mappedIncludes);
                }
            }
        }

        return $this->mappedIncludes;
    }
}
