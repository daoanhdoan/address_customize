<?php

namespace Drupal\address_customize\Plugin\Field\FieldFormatter;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\AddressFormat;
use CommerceGuys\Addressing\AddressFormat\AddressFormatHelper;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface;
use CommerceGuys\Addressing\AddressFormat\FieldOverrides;
use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use CommerceGuys\Addressing\Locale;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use Drupal\address\AddressInterface;
use Drupal\address\FieldHelper;
use Drupal\address\LabelHelper;
use Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'address_customize_default' formatter.
 *
 * @FieldFormatter(
 *   id = "address_customize_default",
 *   label = @Translation("Default(Customize)"),
 *   field_types = {
 *     "address", "address_customize"
 *   },
 * )
 */
class AddressCustomizeDefaultFormatter extends AddressDefaultFormatter implements ContainerFactoryPluginInterface, TrustedCallbackInterface
{
  public static function defaultSettings()
  {
    return [
        'line_break' => TRUE,
        'field_options' => [],
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state)
  {
    $elements = parent::settingsForm($form, $form_state);
    $overrides = $this->getFieldSetting('field_overrides');
    foreach ($overrides as $field => $data) {
      $field_overrides[$field] = $data['override'];
    }
    $fields = [];
    foreach ($field_overrides as $item => $value) {
      if ($value !== 'hidden') {
        $fields[] = $item;
      }
    }
    $element = [];
    $field_options = $this->getSetting('field_options');
    $labels = LabelHelper::getGenericFieldLabels();
    foreach ($fields as $field_index => $field) {
      $element[$field]['field'] = array(
        '#type' => 'markup',
        '#markup' => $labels[$field],
      );
      $field_weight = !empty($field_options[$field]['weight']) ? $field_options[$field]['weight'] : 0;
      $element[$field]['weight'] = array(
        '#type' => 'weight',
        '#title' => $labels[$field],
        '#title_display' => 'invisible',
        '#default_value' => $field_weight,
        '#attributes' => ['class' => ['field-weight']],
      );
      $element[$field]['#attributes']['class'][] = 'draggable';
      $element[$field]['#weight'] = $field_weight;
    }
    uasort($element, [SortArray::class, 'sortByWeightProperty']);
    $element += [
      '#type' => 'table',
      '#header' => [
        'field' => t('Field'),
        'weight' => t('Weight')
      ],
      '#attributes' => [
        'id' => 'field-formatter-address-customize-field-options',
        'class' => ['clearfix']
      ],
      '#tree' => TRUE,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'field-weight',
        ]
      ]
    ];

    $elements['field_options'] = $element;
    $elements['line_break'] = array(
      '#type' => 'checkbox',
      '#title' => t('Inserts HTML line breaks before all newlines in a address'),
      '#default_value' => $this->getSetting('line_break'),
    );
    return $elements;
  }

  /**
   * Builds a renderable array for a single address item.
   *
   * @param \Drupal\address\AddressInterface $address
   *   The address.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array.
   */
  protected function viewElement(AddressInterface $address, $langcode)
  {
    $element = parent::viewElement($address, $langcode);

    $fields = $this->getSetting('field_options');
    uasort($fields, [SortArray::class, 'sortByWeightElement']);
    $localFormat = implode("\n", array_map(function ($item) {
      return "%{$item}";
    }, array_keys($fields)));
    $element += [
      '#settings' => $this->getSettings(),
      '#local_format' => $localFormat
    ];

    return $element;
  }

  /**
   * Inserts the rendered elements into the format string.
   *
   * @param string $content
   *   The rendered element.
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element.
   *
   * @return string
   *   The new rendered element.
   */
  public static function postRender($content, array $element)
  {
    /** @var \CommerceGuys\Addressing\AddressFormat\AddressFormat $address_format */
    $address_format = $element['#address_format'];
    $locale = $element['#locale'];
    $localFormat = $element['#local_format'];
    // Add the country to the bottom or the top of the format string,
    // depending on whether the format is minor-to-major or major-to-minor.
    if (Locale::matchCandidates($address_format->getLocale(), $locale)) {
      $format_string = '%country' . "\n" . $localFormat;
    } else {
      $format_string = $localFormat . "\n" . '%country';
    }

    $replacements = [];
    foreach (Element::getVisibleChildren($element) as $key) {
      $child = $element[$key];
      if (isset($child['#placeholder'])) {
        $replacements[$child['#placeholder']] = $child['#value'] ? $child['#markup'] : '';
      }
    }
    $content = self::replacePlaceholders($format_string, $replacements);
    if ($element['#settings']['line_break']) {
      $content = nl2br($content, FALSE);
    } else {
      $content = str_replace("\n", ", ", $content);
    }

    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks()
  {
    return ['postRender'];
  }
}
