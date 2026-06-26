<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\Core\Url;

/**
 * Helper method for building an embedded Facebook page.
 */
trait FacebookPageThemeTrait {

  /**
   * Build an embedded Facebook page.
   *
   * Uses Facebook's Page Plugin in its iframe form, so no third-party
   * JavaScript SDK needs to be loaded on the page.
   *
   * @param string $page_url
   *   The public URL of the Facebook page to embed.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementFacebookPage(string $page_url): array {
    $src = Url::fromUri('https://www.facebook.com/plugins/page.php', [
      'query' => [
        'href' => $page_url,
        'tabs' => 'timeline',
        'width' => 500,
        'height' => 700,
        'small_header' => 'false',
        'adapt_container_width' => 'true',
        'hide_cover' => 'false',
        'show_facepile' => 'true',
      ],
    ])->toString();

    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'src' => $src,
        'width' => 500,
        'height' => 700,
        // Inline styles are required by Facebook's Page Plugin; the width/auto
        // margin keep it responsive and centered without relying on purge-able
        // Tailwind classes.
        'style' => 'border:none;overflow:hidden;display:block;margin:0 auto;width:100%;max-width:500px;',
        'scrolling' => 'no',
        'frameborder' => '0',
        'allowfullscreen' => 'true',
        'allow' => 'autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share',
        'title' => $this->t('Facebook page'),
      ],
    ];
  }

}
