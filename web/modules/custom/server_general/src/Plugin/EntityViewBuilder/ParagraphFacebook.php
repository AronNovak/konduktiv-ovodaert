<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ThemeTrait\ElementLayoutThemeTrait;
use Drupal\server_general\ThemeTrait\FacebookPageThemeTrait;

/**
 * The "Facebook page" paragraph plugin.
 *
 * @EntityViewBuilder(
 *   id = "paragraph.facebook",
 *   label = @Translation("Paragraph - Facebook page"),
 *   description = "Paragraph view builder for 'Facebook page' bundle."
 * )
 */
class ParagraphFacebook extends EntityViewBuilderPluginAbstract {

  use ElementLayoutThemeTrait;
  use FacebookPageThemeTrait;

  /**
   * Build full view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\paragraphs\ParagraphInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  public function buildFull(array $build, ParagraphInterface $entity): array {
    $link = $this->getLinkFieldValue($entity, 'field_link');
    if (empty($link)) {
      return $build;
    }

    $content = $this->buildElementFacebookPage($link['url']->toString());

    $title = $this->getTextFieldValue($entity, 'field_title');
    $element = $title !== ''
      ? $this->buildElementLayoutTitleAndContent($title, $content)
      : $this->wrapContainerWide($content);

    $build[] = $element;

    return $build;
  }

}
