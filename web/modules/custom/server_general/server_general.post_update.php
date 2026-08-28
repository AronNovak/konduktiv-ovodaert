<?php

/**
 * @file
 * Post update functions for the Server General module.
 */

declare(strict_types=1);

use Drupal\config_pages\Entity\ConfigPages;
use Drupal\config_pages\Entity\ConfigPagesType;

/**
 * Fill the footer config page with the values that were hardcoded in Twig.
 */
function server_general_post_update_seed_footer_config_page(): void {
  $storage = \Drupal::entityTypeManager()->getStorage('config_pages');
  if ($storage->load('footer') instanceof ConfigPages) {
    // Someone has already filled it in; their values win.
    return;
  }

  $type = ConfigPagesType::load('footer');
  $config_page = ConfigPages::create([
    'type' => 'footer',
    'label' => $type->label(),
    'context' => $type->getContextData(),
  ]);

  $config_page->set('field_address', '2011 Budakalász, Kossuth L. u. 7.');
  $config_page->set('field_email', 'petoovodaert@gmail.com');
  $config_page->set('field_tax_number', '18502992-1-13');
  $config_page->set('field_bank_name', 'OTP Bank');
  $config_page->set('field_bank_account', '11702036-20716910');
  $config_page->set('field_facebook_url', [
    'uri' => 'https://www.facebook.com/konduktivovialapitvany/',
  ]);
  $config_page->save();
}
