<?php

namespace Drupal\address_customize\Plugin\Field\FieldWidget;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\AddressFormatHelper;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface;
use CommerceGuys\Addressing\AddressFormat\FieldOverride;
use CommerceGuys\Addressing\AddressFormat\FieldOverrides;
use CommerceGuys\Addressing\Locale;
use Drupal\address\FieldHelper;
use Drupal\address\LabelHelper;
use Drupal\address\Plugin\Field\FieldWidget\AddressDefaultWidget;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;

/**
 * Plugin implementation of the 'address_vn' widget.
 *
 * @FieldWidget(
 *   id = "address_customize",
 *   label = @Translation("Address Customize"),
 *   field_types = {
 *     "address", "address_customize"
 *   },
 * )
 */
class AddressCustomizeWidget extends AddressDefaultWidget implements ContainerFactoryPluginInterface
{
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings()
  {
    return [
        'hide_country_code' => FALSE,
        'field_options' => [],
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state)
  {
    $overrides = $this->getFieldSetting('field_overrides');
    $field_overrides = [];
    foreach ($overrides as $field => $data) {
      $field_overrides[$field] = $data['override'];
    }
    $format_strings = [];
    foreach ($field_overrides as $item => $value) {
      if ($value !== 'hidden') {
        $format_strings[] = "%{$item}";
      }
    }
    $field_overrides = new FieldOverrides($field_overrides);

    $format_string = implode("\n", $format_strings);

    $elements = [];
    $field_options = $this->getSetting('field_options');

    $grouped_fields = AddressFormatHelper::getGroupedFields($format_string, $field_overrides);
    $labels = LabelHelper::getGenericFieldLabels();
    $weight = 0;
    foreach ($grouped_fields as $line_index => $line_fields) {
      foreach ($line_fields as $field_index => $field) {
        $elements[$field]['field'] = array(
          '#type' => 'markup',
          '#markup' => $labels[$field],
        );

        $field_weight = !empty($field_options[$field]['weight']) ? $field_options[$field]['weight'] : $weight++;
        $title = !empty($field_options[$field]['title']) ? $field_options[$field]['title'] : $labels[$field];
        $placeholder = !empty($field_options[$field]['placeholder']) ? $field_options[$field]['placeholder'] : "";
        $elements[$field]['title'] = [
          '#type' => 'textfield',
          '#title' => $labels[$field],
          '#title_display' => 'invisible',
          '#default_value' => $title,
          '#required' => TRUE
        ];

        $elements[$field]['placeholder'] = array(
          '#type' => 'textfield',
          '#title' => $labels[$field],
          '#title_display' => 'invisible',
          '#default_value' => $placeholder,
          '#attributes' => ['class' => ['field-placeholder']],
        );
        $elements[$field]['weight'] = array(
          '#type' => 'weight',
          '#title' => $labels[$field],
          '#title_display' => 'invisible',
          '#default_value' => $field_weight,
          '#attributes' => ['class' => ['field-weight']],
        );
        $elements[$field]['#attributes']['class'][] = 'draggable';
        $elements[$field]['#weight'] = $field_weight;
      }
    }
    uasort($elements, [SortArray::class, 'sortByWeightProperty']);
    $elements += [
      '#type' => 'table',
      '#header' => [
        'field' => t('Field'),
        'title' => t('Title'),
        'placeholder' => t('Placeholder'),
        'weight' => t('Weight')
      ],
      '#attributes' => [
        'id' => 'field-widget-address-customize-field-options',
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

    $form['field_options'] = $elements;
    $form['hide_country_code'] = array(
      '#type' => 'checkbox',
      '#title' => t('Hide the country when only one is available'),
      '#default_value' => $this->getSetting('hide_country_code'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
  {
    $element += parent::formElement($items, $delta, $element, $form, $form_state);
    $element['address']['#field_options'] = [];

    if ($field_options = $this->getSetting('field_options')) {
      foreach ($field_options as $field => $options) {
        $property = FieldHelper::getPropertyName($field);
        $element['address']['#field_options'][$property] = $options;
      }
    }
    if(!empty($element['address']['#available_countries'])) {
      $element['address']['#hide_country_code'] = count($element['address']['#available_countries']) == 1 && $this->getSetting('hide_country_code') ? TRUE : FALSE;
    }

    /*if (empty($element['address']['default_value']['country_code'])) {
      $element['address']['#default_value']['country_code'] = \Drupal::config('system.date')->get('country.default');
    }*/
    $element['address']['#after_build'][] = [get_class($this), 'customizeAfterBuild'];


    return $element;
  }

  /**
   * Form API callback: Makes all address field properties optional.
   */
  public static function customizeAfterBuild(array $element, FormStateInterface $form_state)
  {
    if ($field_options = $element['#field_options']) {
      foreach ($field_options as $field => $options) {
        if (!empty($element[$field])) {
          $element[$field]['#weight'] = $options['weight'];
          $element[$field]['#title'] = t($options['title']);
          if (!empty($options['placeholder'])){
            $element[$field]['#attributes']['placeholder'] = t($options['placeholder']);
          }
        }
      }
    }

    uasort($element, [SortArray::class, 'sortByWeightProperty']);

    if (!empty($element['#hide_country_code'])) {
      $element['country_code']['#attributes']['class'][] = 'visually-hidden';
    }

    return $element;
  }
}
