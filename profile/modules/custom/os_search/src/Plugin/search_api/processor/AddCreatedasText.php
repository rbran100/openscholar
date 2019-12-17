<?php

namespace Drupal\os_search\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds the common type to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "add_created_as_text",
 *   label = @Translation("Custom Entity Created (text) field"),
 *   description = @Translation("Adds common created date field for all entites."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class AddCreatedasText extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Custom Created Date (text)'),
        'description' => $this->t('Common Created Date for all entities.'),
        'type' => 'string',
        'is_list' => FALSE,
        'processor_id' => $this->getPluginId(),
      ];
      $properties['custom_date'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $object = $item->getOriginalObject()->getValue();
    $custom_bundle = $object->getEntityTypeId();

    $custom_date = '';
    // Get bundle or type based on Entity Type.
    if ($custom_bundle == 'node') {
      $custom_date = $object->getCreatedTime();
    }
    if ($custom_bundle == 'bibcite_reference') {
      $custom_date = $object->get('created')->getValue()[0]['value'];
    }

    if ($custom_bundle) {
      $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'custom_date');

      foreach ($fields as $field) {
        $field->addValue($custom_date);
      }
    }
  }

}
