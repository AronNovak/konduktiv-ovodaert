<?php

declare(strict_types=1);

namespace Drupal\petoovoda_donation\Plugin\EntityViewBuilder;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\petoovoda_donation\HctQrPayloadBuilder;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ThemeTrait\ElementLayoutThemeTrait;
use Drupal\server_general\ThemeTrait\ElementWrapThemeTrait;
use Drupal\server_general\ThemeTrait\Enum\FontWeightEnum;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The "Donation QR" paragraph plugin.
 *
 * @EntityViewBuilder(
 *   id = "paragraph.donation_qr",
 *   label = @Translation("Paragraph - Donation QR"),
 *   description = "Paragraph view builder for 'Donation QR' bundle."
 * )
 */
class ParagraphDonationQr extends EntityViewBuilderPluginAbstract {

  use ElementLayoutThemeTrait;
  use ElementWrapThemeTrait;

  /**
   * Validity window of the QR payload in seconds.
   */
  protected const VALIDITY_SECONDS = 86400;

  /**
   * Render cache lifetime; must stay well below VALIDITY_SECONDS.
   */
  protected const CACHE_MAX_AGE = 3600;

  /**
   * The logger channel.
   */
  protected LoggerChannelInterface $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, EntityRepositoryInterface $entity_repository, LanguageManagerInterface $language_manager, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $current_user, $entity_repository, $language_manager);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('logger.factory')->get('petoovoda_donation'),
    );
  }

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
    $beneficiary = $this->getTextFieldValue($entity, 'field_beneficiary');
    $iban = $this->getTextFieldValue($entity, 'field_iban');
    $bic = $this->getTextFieldValue($entity, 'field_bic');
    $message = $this->getTextFieldValue($entity, 'field_payment_message');
    $amount = $entity->hasField('field_amount') && !$entity->get('field_amount')->isEmpty()
      ? (int) $entity->get('field_amount')->getString()
      : NULL;

    try {
      $payload = HctQrPayloadBuilder::build($bic, $beneficiary, $iban, $amount, $message, self::VALIDITY_SECONDS);
    }
    catch (\InvalidArgumentException $e) {
      // An editor entered invalid bank details; skip the element instead of
      // breaking the whole page.
      $this->logger->error('Donation QR paragraph @id skipped: @message', [
        '@id' => $entity->id(),
        '@message' => $e->getMessage(),
      ]);
      return $build;
    }

    $elements = [];
    $elements[] = $this->buildQrImage($payload);

    $details = [];
    $details[] = $this->wrapTextCenter($this->wrapTextFontWeight($beneficiary, FontWeightEnum::Bold));
    $details[] = $this->wrapTextCenter($this->formatIban($iban));
    if ($amount !== NULL) {
      $details[] = $this->wrapTextCenter(number_format($amount, 0, ',', ' ') . ' Ft');
    }
    $elements[] = $this->wrapContainerVerticalSpacingTiny($details);

    $content = $this->wrapContainerVerticalSpacing($elements);

    $title = $this->getTextFieldValue($entity, 'field_title');
    $element = $title !== ''
      ? $this->buildElementLayoutTitleAndContent($title, $content)
      : $this->wrapContainerWide($content);

    // The payload carries an expiry timestamp, so the render cache must not
    // outlive the validity window.
    $element['#cache']['max-age'] = self::CACHE_MAX_AGE;

    $build[] = $element;

    return $build;
  }

  /**
   * Build the QR code as an inline image with a base64 SVG source.
   *
   * @param string $payload
   *   The HCT QR payload.
   *
   * @return array
   *   Render array.
   */
  protected function buildQrImage(string $payload): array {
    $options = new QROptions([
      'version' => QRCode::VERSION_AUTO,
      'outputType' => QRCode::OUTPUT_MARKUP_SVG,
      // The MNB standard mandates error correction level M.
      'eccLevel' => QRCode::ECC_M,
      'imageBase64' => TRUE,
    ]);
    $data_uri = (new QRCode($options))->render($payload);

    return [
      '#type' => 'inline_template',
      '#template' => '<img src="{{ src }}" alt="{{ alt }}" width="256" height="256" class="mx-auto">',
      '#context' => [
        'src' => $data_uri,
        'alt' => $this->t('Donation QR code'),
      ],
    ];
  }

  /**
   * Format an IBAN in groups of four characters for readability.
   *
   * @param string $iban
   *   The IBAN without spaces.
   *
   * @return string
   *   The formatted IBAN.
   */
  protected function formatIban(string $iban): string {
    $iban = strtoupper(str_replace(' ', '', $iban));
    return trim(chunk_split($iban, 4, ' '));
  }

}
