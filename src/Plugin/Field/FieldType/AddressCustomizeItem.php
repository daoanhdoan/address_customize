<?php

namespace Drupal\address_customize\Plugin\Field\FieldType;

use Drupal\address\Plugin\Field\FieldType\AddressItem;
use Drupal\address\Plugin\Field\FieldType\AvailableCountriesTrait;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'address' field type.
 *
 * @FieldType(
 *   id = "address_customize",
 *   label = @Translation("Address Customize"),
 *   description = @Translation("An entity field containing a postal address"),
 *   category = @Translation("Address"),
 *   default_widget = "address_default",
 *   default_formatter = "address_default",
 *   list_class = "\Drupal\address\Plugin\Field\FieldType\AddressFieldItemList"
 * )
 */
class AddressCustomizeItem extends AddressItem {

  use AvailableCountriesTrait;

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['address_line1'] = [
      'type' => 'text',
      'size' => 'small',
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'country_code';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['address_line1'] = DataDefinition::create('text')
      ->setLabel(t('The first line of the address block'));
    return $properties;
  }
}
