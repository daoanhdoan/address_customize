<?php

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * @see hook_views_data_alter()
 */
function address_customize_views_data_alter(array &$data)
{
  foreach ($data as $table_name => &$table_data) {
    $entity_type_id = !empty($table_data['table']['entity type']) ? $table_data['table']['entity type'] : NULL;
    if (!$entity_type_id) {
      continue;
    }
    $fieldDefinitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($entity_type_id);
    foreach ($table_data as $name => $field) {
      $entityField = !empty($field['entity field']) ? $field['entity field'] : NULL;
      if (isset($fieldDefinitions[$entityField])) {
        /** @var FieldDefinitionInterface $fieldDefinition */
        $fieldDefinition = $fieldDefinitions[$entityField];
        if ($fieldDefinition->getType() === 'address_country') {
          $data[$table_name][$name]['filter']['id'] = 'country';
        }
      }
    }
  }
}
