<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\config_pages\Entity\ConfigPages;
use Drupal\config_pages\Entity\ConfigPagesType;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test the locked pages functionality.
 */
class ServerGeneralLockedPagesTest extends ServerGeneralTestBase {

  /**
   * Test locked Homepage can't be deleted.
   */
  public function testLockedHomepage() {
    // The locked homepage now lives under a stable UUID instead of a fixed
    // English title (the foundation rebrand renamed it).
    $homepage = \Drupal::service('entity.repository')
      ->loadEntityByUuid('node', '59aaa9d4-8a47-436a-b8f3-00484c08c124');
    $this->assertNotNull($homepage, 'Homepage node must exist as default content.');

    try {
      $homepage->delete();
      // Fail if exception is not thrown on line above.
      $this->fail('Expected locked pages deletion exception not thrown.');
    }
    catch (\Exception $exception) {
      $this->assertEquals("This node is locked and can't be removed", $exception->getMessage());
    }

    $this->drupalGet($homepage->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    $homepage->setUnpublished();
    $homepage->save();

    $this->assertEquals(TRUE, $homepage->isPublished());

    $user = $this->createUser();
    $user->addRole('content_editor');
    $user->save();
    $this->drupalLogin($user);

    $this->drupalGet($homepage->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()
      ->linkByHrefNotExists("/node/{$homepage->id()}/delete");
    $this->assertSession()->elementNotExists('css', 'a#edit-delete');
    $this->assertSession()->elementNotExists('css', 'input#edit-status-value');

    $this->drupalGet("/node/{$homepage->id()}/delete");
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    $this->drupalGet($homepage->toUrl());
    $this->assertSession()
      ->linkByHrefNotExists("/node/{$homepage->id()}/delete");

    $this->drupalGet('/admin/content');
    $this->assertSession()
      ->linkByHrefNotExists("/node/{$homepage->id()}/delete");
  }

  /**
   * Test a general locked landing page.
   */
  public function testLockedLandingPage() {
    $user = $this->createUser();
    $user->addRole('content_editor');
    $user->save();
    $this->drupalLogin($user);

    // Check not locked page for admin.
    $node = $this->createNode([
      'title' => 'Not locked page',
      'type' => 'landing_page',
      'moderation_state' => 'published',
    ]);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->linkByHrefExists("/node/{$node->id()}/delete");

    $this->drupalGet("/node/{$node->id()}/delete");
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    // Make page locked.
    $main_settings = $this->loadOrCreateConfigPages('main_settings');

    $old_value = $main_settings->get('field_locked_pages')->getValue();

    $main_settings->get('field_locked_pages')->appendItem(['target_id' => $node->id()]);
    $main_settings->save();

    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->linkByHrefNotExists("/node/{$node->id()}/delete");

    $this->drupalGet("/node/{$node->id()}/delete");
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    // Check locked node for anonymous.
    $this->drupalLogout();
    $this->drupalGet("/node/{$node->id()}/delete");
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    // Restore old locked pages value so the created node can be deleted.
    $main_settings->set('field_locked_pages', $old_value);
    $main_settings->save();
  }

  /**
   * Create or load a config pages entity.
   *
   * @param string $config_pages_id
   *   The ID of the config pages entity.
   *
   * @return \Drupal\config_pages\ConfigPagesInterface
   *   The config_pages entity.
   */
  protected function loadOrCreateConfigPages(string $config_pages_id) {
    // We try to load config_pages of type "main_settings".
    $config_pages_storage = \Drupal::service('config_pages.loader');
    /** @var \Drupal\config_pages\Entity\ConfigPages|null $config_pages */
    $config_pages = $config_pages_storage->load($config_pages_id);

    if (!empty($config_pages)) {
      return $config_pages;
    }

    // Create a new config page.
    $type = ConfigPagesType::load($config_pages_id);
    $config_pages = ConfigPages::create([
      'type' => $config_pages_id,
      'context' => $type->getContextData(),
    ]);
    $config_pages->save();
    $this->markEntityForCleanup($config_pages);

    return $config_pages;
  }

}
