<?php

namespace Drupal\address_customize\EventSubscriber;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\AddressFormatEvent;
use Drupal\address\Event\SubdivisionsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to Address events for testing.
 *
 * @see \Drupal\Tests\address\FunctionalJavascript\AddressDefaultWidgetTest::testEvents()
 */
class AddressCustomizeEventSubscriber implements EventSubscriberInterface
{

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents()
  {
    $events[AddressEvents::ADDRESS_FORMAT][] = ['onAddressFormat'];
    return $events;
  }

  /**
   * Alters the available countries.
   *
   * @param \Drupal\address\Event\AvailableCountriesEvent $event
   *   The available countries event.
   */
  public function onAddressFormat(AddressFormatEvent $event)
  {
    $definition = $event->getDefinition();
    $definition['format'] = implode("\n", AddressField::getTokens());
/*    $definition['administrative_area_type'] = 'province';
    $definition['locality_type'] = 'city';
    $definition['dependent_locality_type'] = 'neighborhood';*/
    $definition['subdivision_depth'] = 0;
    $event->setDefinition($definition);
  }
}
