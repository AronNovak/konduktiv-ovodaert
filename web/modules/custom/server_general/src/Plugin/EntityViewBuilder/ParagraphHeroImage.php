<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Link;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ThemeTrait\HeroThemeTrait;

/**
 * The "Hero image" paragraph plugin.
 *
 * @EntityViewBuilder(
 *   id = "paragraph.hero_image",
 *   label = @Translation("Paragraph - Hero image"),
 *   description = "Paragraph view builder for 'Hero image' bundle."
 * )
 */
class ParagraphHeroImage extends EntityViewBuilderPluginAbstract {

  use HeroThemeTrait;

  const RESPONSIVE_IMAGE_STYLE_ID = 'hero';

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
    $value = $this->getLinkFieldValue($entity, 'field_link');

    $image = $this->buildMediaResponsiveImage($entity, 'field_image', self::RESPONSIVE_IMAGE_STYLE_ID);
    if ($image) {
      // The hero sits at the top of the page, so it is normally the Largest
      // Contentful Paint element. Core defaults any image with known dimensions
      // to loading="lazy", which delays the very request the page is judged on.
      $image['#attributes']['loading'] = 'eager';
      $image['#attributes']['fetchpriority'] = 'high';
    }

    $element = $this->buildElementHeroImage(
      $image,
      $this->getTextFieldValue($entity, 'field_title'),
      $this->getTextFieldValue($entity, 'field_subtitle'),
      $value ? Link::fromTextAndUrl($value['title'], $value['url']) : NULL,
    );
    $build[] = $element;

    return $build;
  }

}
