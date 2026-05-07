<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'landing_page' content type.
 */
class ServerGeneralNodeLandingPageTest extends ServerGeneralNodeTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'landing_page';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [
      'field_is_title_hidden',
      'field_paragraphs',
    ];
  }

  /**
   * Test the permissions and available paragraphs.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testGeneral() {
    // The form's "Add X" buttons inherit the paragraph type label, which is
    // Hungarian on this site. Asserting on a stable per-button selector
    // (data-drupal-selector value contains the bundle machine name) keeps
    // this test from breaking when labels are re-translated.
    $bundles = [
      'hero_image',
      'text',
      'info_cards',
      'cta',
      'quick_links',
    ];

    $assert = $this->assertSession();
    // Login as a content editor.
    $user = $this->createUser();
    $user->addRole('administrator');
    $user->save();
    $this->drupalLogin($user);
    $this->drupalGet('/node/add/landing_page');
    $assert->elementExists('css', '.field--name-field-paragraphs');
    foreach ($bundles as $bundle) {
      $assert->elementExists('css', "input[name=\"field_paragraphs_{$bundle}_add_more\"]");
    }
  }

}
