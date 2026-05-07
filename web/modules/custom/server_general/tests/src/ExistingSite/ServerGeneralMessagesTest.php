<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Symfony\Component\HttpFoundation\Response;

/**
 * Tests for status messages display.
 */
class ServerGeneralMessagesTest extends ServerGeneralTestBase {

  /**
   * Test that status messages are displayed when saving a node.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testNodeSaveMessage() {
    // Create a landing page node to edit.
    $node = $this->createNode([
      'title' => 'Landing Page',
      'type' => 'landing_page',
    ]);

    // Login as admin.
    $user = $this->createUser();
    $user->addRole('administrator');
    $user->save();
    $this->drupalLogin($user);

    // Visit the node edit form.
    $this->drupalGet($node->toUrl('edit-form'));
    $assert = $this->assertSession();
    $assert->statusCodeEquals(Response::HTTP_OK);

    // Use the form field's machine name so the assertion isn't sensitive
    // to the UI language ("Title" in English, "Cím" in Hungarian).
    $this->getCurrentPage()->fillField('title[0][value]', 'Updated Landing Page Title');

    // Save the node — submit button has a stable data-drupal-selector value.
    $this->getCurrentPage()->find('css', '[data-drupal-selector="edit-submit"]')->click();

    // Assert that the messages container exists.
    $assert->elementExists('css', '[data-drupal-messages]');

    // Status message wrapper exists, regardless of which language the
    // aria-label string itself is rendered in.
    $assert->elementExists('css', '[role="contentinfo"].messages--status');
  }

}
