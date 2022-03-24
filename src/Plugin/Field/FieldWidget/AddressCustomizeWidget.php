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
        'address_box' => FALSE,
        'address_box_row' => 4,
        'wrapper_container' => FALSE
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
    $address_format = \Drupal::service('address.address_format_repository')->get(\Drupal::configFactory()->get('system.date')->get('country.default'));
    $required_fields = AddressFormatHelper::getRequiredFields($address_format, $field_overrides);
    $labels = LabelHelper::getFieldLabels($address_format);
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
    $form['address_box'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use Address Line as Address Box'),
      '#default_value' => $this->getSetting('address_box'),
    );
    $form['address_box_row'] = array(
      '#type' => 'number',
      '#title' => t('Address box rows'),
      '#default_value' => $this->getSetting('address_box_row'),
    );
    $form['wrapper_container'] = array(
      '#type' => 'checkbox',
      '#title' => t('Wrapper by container'),
      '#default_value' => $this->getSetting('wrapper_container'),
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
    $element['address']['#address_box'] = $this->getSetting('address_box');
    $element['address']['#address_box_row'] = $this->getSetting('address_box_row');

    if (!empty($element['address']['#available_countries'])) {
      $element['address']['#hide_country_code'] = count($element['address']['#available_countries']) == 1 && $this->getSetting('hide_country_code') ? TRUE : FALSE;
    }

    if (empty($element['address']['default_value']['country_code']) && !empty($element['address']['#hide_country_code'])) {
      $element['address']['#default_value']['country_code'] = \Drupal::config('system.date')->get('country.default');
    }
    $element['address']['#after_build'][] = [get_class($this), 'customizeAfterBuild'];
    $element['address']['#process'] = [
      'Drupal\address\Element\Address::processAddress',
      'Drupal\address\Element\Address::processGroup',
      [get_class($this), 'customAddressProcess']
    ];
    if ($this->getSetting('wrapper_container')) {
      $element['#theme_wrappers'] = ['container'];
    }

    return $element;
  }

  /**
   * Form API callback: Makes all address field properties optional.
   */
  public static function customizeAfterBuild(array $element, FormStateInterface $form_state)
  {
    /*if ($field_options = $element['#field_options']) {
      $state = [
        'visible' => [
          ':input[name="' . $element['address_box']['#name'] . '"]' => ['checked' => TRUE],
        ],
      ];
      foreach ($field_options as $field => $options) {
        if (!empty($element[$field])) {
          $element[$field]['#weight'] = $options['weight'];
          $element[$field]['#title'] = t($options['title']);
          if (!empty($options['placeholder'])) {
            $element[$field]['#attributes']['placeholder'] = t($options['placeholder']);
          }
          if ($field !== 'address_line1' && !empty($element['#address_box'])) {
            $element[$field]['#states'] = $state;
          }
        }
      }
    }

    uasort($element, [SortArray::class, 'sortByWeightProperty']);

    if (!empty($element['#hide_country_code'])) {
      $element['country_code']['#attributes']['class'][] = 'visually-hidden';
    }*/
    return $element;
  }

  public static function customAddressProcess(array &$element, FormStateInterface $form_state, array &$complete_form)
  {
    if (!empty($element['address_line1']) && !empty($element['#address_box'])) {
      $label = !empty($element['#field_options']['address_line1']['title']) ? $element['#field_options']['address_line1']['title'] : t('Address Street');
      $element['address_box'] = array(
        '#type' => 'checkbox',
        '#title' => t('Use @label as Address Box', ['@label' => $label]),
        '#attributes' => array('checked' => !empty($element['#address_box']) ? TRUE : FALSE),
        '#weight' => -99
      );
    }

    if ($field_options = $element['#field_options']) {
      $state = [
        'visible' => [
          ':input[name="' . $element['#name'] . '[address_box]"]' => ['checked' => FALSE],
        ],
      ];
      foreach ($field_options as $field => $options) {
        if (!empty($element[$field])) {
          $element[$field]['#weight'] = $options['weight'];
          $element[$field]['#title'] = t($options['title']);
          if (!empty($options['placeholder'])) {
            $element[$field]['#attributes']['placeholder'] = t($options['placeholder']);
          }

          if ($field !== 'address_line1' && !empty($element['#address_box'])) {
            $element[$field]['#states'] = $state;
          }
        }
      }
    }

    uasort($element, [SortArray::class, 'sortByWeightProperty']);

    if (!empty($element['#hide_country_code'])) {
      $element['country_code']['#attributes']['class'][] = 'visually-hidden';
    }

    if (!empty($element['address_line1']) && !empty($element['#address_box'])) {
      $element['address_line1']['#type'] = 'textarea';
      $element['address_line1']['#row'] = !empty($element['#address_box_row']) ? $element['#address_box_row'] : '4';
    }

    return $element;
  }
}
