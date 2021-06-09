<?php

namespace SoapBox\AgendaTemplateClient\RemoteResources;

use Illuminate\Support\Collection;
use Art4\JsonApiClient\V1\Attributes;
use Art4\JsonApiClient\V1\ResourceItem;

class Resource
{
    public $resourceItem;
    private $mappedIncluded;

    /**
     * Create a resource item using the Document passed in
     *
     * @param Art4\JsonApiClient\V1\Document $json
     */
    public function __construct(ResourceItem $resourceItem, Collection $mappedIncluded)
    {
        $this->resourceItem = $resourceItem;
        $this->mappedIncluded = $mappedIncluded;
    }

    /**
     * Get a specific attribute of the resource item
     * e.g. $var->id
     *
     * @param string $attribute
     *
     * @return mixed
     */
    public function __get(string $attribute)
    {
        $attribute = str_replace("_", "-", $attribute);
        return $this->getAttributes()->get($attribute);
    }

    /**
     * Get the id of the resource item
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->resourceItem->get('id');
    }

    /**
     * Get all attributes for the resource item
     *
     * @return array
     */
    public function getAttributes(): Attributes
    {
        return $this->resourceItem->get('attributes');
    }


    /**
     * Get the relationship of the resource item
     *
     * @return Illuminate\Support\Collection;
     */
    public function getRelationship(string $relationship): Collection
    {
        $collect = collect([]);

        if ($this->resourceItem->has('relationships.' . $relationship . '.data')) {
            foreach ($this->resourceItem->get('relationships.' . $relationship . '.data')->getKeys() as $key) {
                $resourceIdentifier = $this->resourceItem->get('relationships.' . $relationship . '.data')->get($key);
                $resourceKey = $resourceIdentifier->get('type') . '-' . $resourceIdentifier->get('id');

                $collect[] = $this->mappedIncluded->get($resourceKey);
            }
        }
        return $collect;
    }
}
