<?php

namespace Drupal\address_customize\Plugin\Field\FieldType;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\FieldOverride;
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

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = parent::defaultFieldSettings();
    $settings['field_overrides'] = [
      AddressField::GIVEN_NAME => ['override' => FieldOverride::HIDDEN],
      AddressField::ADDITIONAL_NAME => ['override' => FieldOverride::HIDDEN],
      AddressField::FAMILY_NAME => ['override' => FieldOverride::HIDDEN],
      AddressField::ORGANIZATION => ['override' => FieldOverride::HIDDEN],
      AddressField::ADDRESS_LINE1 => ['override' => FieldOverride::OPTIONAL],
      AddressField::ADDRESS_LINE2 => ['override' => FieldOverride::HIDDEN],
      AddressField::POSTAL_CODE => ['override' => FieldOverride::OPTIONAL],
      AddressField::SORTING_CODE => ['override' => FieldOverride::HIDDEN],
      AddressField::DEPENDENT_LOCALITY => ['override' => FieldOverride::OPTIONAL],
      AddressField::LOCALITY => ['override' => FieldOverride::OPTIONAL],
      AddressField::ADMINISTRATIVE_AREA => ['override' => FieldOverride::OPTIONAL],
    ];
    return $settings;
  }
}
