<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\server_general\Traits\ServerGeneralMailTestTrait;

/**
 * A model test case for email-testing using traits from Drupal Test Traits.
 */
class ServerGeneralMailTest extends ServerGeneralTestBase {

  use ServerGeneralMailTestTrait;

  /**
   * Test one-time login links.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testOneTimeLoginLinkEmail() {
    $this->resetOutgoingMails();
    $this->assertOutgoingMailNumber(0);
    $this->drupalGet('user/password');
    $this->getCurrentPage()->fillField('edit-name', 'joe@example.com');
    // Use the stable form selector — submit button label is translated.
    $this->getCurrentPage()->find('css', '[data-drupal-selector="edit-submit"]')->click();
    $this->assertOutgoingMailNumber(1);
    // Email subject/body localizes; assert the user identity makes it through.
    $this->assertOutgoingMailContains('JoeDoe');
  }

}
