<?php declare(strict_types=1);

namespace Table\Form\Element;

use Common\Form\Element\TraitGroupByOwner;
use Common\Form\Element\TraitOptionalElement;
use Omeka\Api\Representation\UserRepresentation;
use Omeka\Form\Element\AbstractGroupByOwnerSelect;

class TablesSelect extends AbstractGroupByOwnerSelect
{
    use TraitGroupByOwner;
    use TraitOptionalElement;

    public function getResourceName()
    {
        return 'tables';
    }

    public function getValueLabel($resource)
    {
        return $resource->displayTitle();
    }

    public function getValueOptions(): array
    {
        if (!$this->getOption('slug_as_value')) {
            return $this->getValueOptionsFix();
        }

        $query = $this->getOption('query');
        if (!is_array($query)) {
            $query = [];
        }

        $resourceReps = $this->getApiManager()->search($this->getResourceName(), $query)->getContent();

        // Provide a way to filter the resource representations prior to
        // building the value options.
        $callback = $this->getOption('filter_resource_representations');
        if (is_callable($callback)) {
            $resourceReps = $callback($resourceReps);
        }

        $valueOptions = [];

        if ($this->getOption('disable_group_by_owner')) {
            // Group alphabetically by resource label without grouping by owner.
            $resources = [];
            foreach ($resourceReps as $resource) {
                // Use slug as value.
                $resources[$this->getValueLabel($resource)][] = $resource->slug();
            }
            ksort($resources);
            foreach ($resources as $label => $ids) {
                foreach ($ids as $id) {
                    $valueOptions[$id] = $label;
                }
            }
        } else {
            // Group alphabetically by owner email.
            $resourceOwners = [];
            foreach ($resourceReps as $resource) {
                $owner = $resource->owner();
                $index = $owner ? $owner->email() : null;
                $resourceOwners[$index]['owner'] = $owner;
                $resourceOwners[$index]['resources'][] = $resource;
            }
            ksort($resourceOwners);

            foreach ($resourceOwners as $resourceOwner) {
                if (!$resourceOwner['resources']) {
                    continue;
                }
                $options = [];
                foreach ($resourceOwner['resources'] as $resource) {
                    // Use slug as value.
                    $options[$resource->slug()] = $this->getValueLabel($resource);
                }
                $owner = $resourceOwner['owner'];
                if ($owner instanceof UserRepresentation) {
                    $label = sprintf('%s (%s)', $owner->name(), $owner->email());
                    $index = $owner->id();
                } else {
                    $label = '[No owner]'; // @translate
                    $index = '-0';
                }
                // An index is required to prepend option "0" with array union.
                $valueOptions[$index] = ['label' => $label, 'options' => $options];
            }
        }

        return $this->prependValuesOptions($valueOptions);
    }
}
