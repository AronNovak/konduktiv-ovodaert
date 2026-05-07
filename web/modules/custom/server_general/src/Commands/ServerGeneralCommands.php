<?php

declare(strict_types=1);

namespace Drupal\server_general\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * Server General Drush commands.
 */
class ServerGeneralCommands extends DrushCommands {

  /**
   * UUID of the landing_page node that acts as the site homepage.
   *
   * Looking up by UUID (rather than by title) keeps this command working
   * after the site is rebranded and the homepage title changes.
   */
  protected const HOMEPAGE_UUID = '59aaa9d4-8a47-436a-b8f3-00484c08c124';

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Entity Repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * Config Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, ConfigFactoryInterface $config_factory) {
    parent::__construct();
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->configFactory = $config_factory;
  }

  /**
   * Command description here.
   *
   * @usage server_general:set-homepage
   *   Sets the homepage to the well-known landing_page node (by UUID).
   *
   * @command server_general:set-homepage
   * @aliases set-homepage
   */
  public function setHomepageAfterInstall(): void {
    $homepage = $this->entityRepository->loadEntityByUuid('node', self::HOMEPAGE_UUID);
    /** @var \Drush\Log\DrushLoggerManager|null $logger */
    $logger = $this->logger();
    if (!$homepage) {
      $logger->error(dt('Unable to find a landing_page node with UUID @uuid.', [
        '@uuid' => self::HOMEPAGE_UUID,
      ]));
      return;
    }

    $front = "/node/{$homepage->id()}";
    $config = $this->configFactory->getEditable('system.site');
    $config->set('page.front', $front);
    $config->save();
    $logger->notice(dt('Homepage set to @front.', [
      '@front' => $front,
    ]));
  }

}
