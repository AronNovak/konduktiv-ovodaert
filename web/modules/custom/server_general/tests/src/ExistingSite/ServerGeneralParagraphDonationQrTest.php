<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\server_general\Traits\ParagraphCreationTrait;
use Drupal\petoovoda_donation\HctQrPayloadBuilder;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'Donation QR' paragraph type.
 */
class ServerGeneralParagraphDonationQrTest extends ServerGeneralParagraphTestBase {

  use ParagraphCreationTrait;

  const TEST_IBAN = 'HU42117730161111101800000000';

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'donation_qr';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_beneficiary',
      'field_iban',
      'field_bic',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [
      'field_title',
      'field_amount',
      'field_payment_message',
    ];
  }

  /**
   * Test the HCT payload structure.
   */
  public function testPayload() {
    $payload = HctQrPayloadBuilder::build('OTPVHUHB', 'Alapítvány', self::TEST_IBAN, 5000, 'Adomány');

    $lines = explode("\n", $payload);
    // 17 fields, each LF-terminated, so the split yields a trailing empty
    // element.
    $this->assertCount(18, $lines);
    $this->assertSame('', end($lines));
    $this->assertSame('HCT', $lines[0]);
    $this->assertSame('001', $lines[1]);
    $this->assertSame('1', $lines[2]);
    $this->assertSame('OTPVHUHB', $lines[3]);
    $this->assertSame('Alapítvány', $lines[4]);
    $this->assertSame(self::TEST_IBAN, $lines[5]);
    $this->assertSame('HUF5000', $lines[6]);
    $this->assertMatchesRegularExpression('/^\d{14}\+\d$/', $lines[7]);
    $this->assertSame('Adomány', $lines[9]);
    $this->assertLessThanOrEqual(345, strlen($payload));

    $this->expectException(\InvalidArgumentException::class);
    HctQrPayloadBuilder::build('OTPVHUHB', 'Alapítvány', 'DE12345678901234567890');
  }

  /**
   * Test render of the paragraph.
   */
  public function testRender() {
    $paragraph = $this->createParagraph([
      'type' => $this->getEntityBundle(),
      'field_title' => 'Támogassa óvodánkat',
      'field_beneficiary' => 'Konduktív Óvodáért Alapítvány',
      'field_iban' => self::TEST_IBAN,
      'field_bic' => 'OTPVHUHB',
      'field_amount' => 5000,
      'field_payment_message' => 'Adomány',
    ]);

    $user = $this->createUser();
    $node = $this->createNode([
      'title' => 'Landing Page',
      'type' => 'landing_page',
      'uid' => $user->id(),
      'field_paragraphs' => [
        $this->getParagraphReferenceValues($paragraph),
      ],
      'moderation_state' => 'published',
    ]);
    $node->setPublished()->save();

    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    $selector = '.paragraph--type--donation-qr';
    $this->assertSession()->elementTextContains('css', $selector, 'Támogassa óvodánkat');
    $this->assertSession()->elementTextContains('css', $selector, 'Konduktív Óvodáért Alapítvány');
    $this->assertSession()->elementTextContains('css', $selector, 'HU42 1177 3016 1111 1018 0000 0000');
    $this->assertSession()->elementTextContains('css', $selector, '5 000 Ft');
    $this->assertSession()->elementAttributeContains('css', $selector . ' img', 'src', 'data:image/svg+xml');
  }

  /**
   * Test that invalid bank details skip the element instead of crashing.
   */
  public function testInvalidIbanIsSkipped() {
    $paragraph = $this->createParagraph([
      'type' => $this->getEntityBundle(),
      'field_beneficiary' => 'Konduktív Óvodáért Alapítvány',
      'field_iban' => 'HU123',
      'field_bic' => 'OTPVHUHB',
    ]);

    $user = $this->createUser();
    $node = $this->createNode([
      'title' => 'Landing Page',
      'type' => 'landing_page',
      'uid' => $user->id(),
      'field_paragraphs' => [
        $this->getParagraphReferenceValues($paragraph),
      ],
      'moderation_state' => 'published',
    ]);
    $node->setPublished()->save();

    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->elementNotExists('css', '.paragraph--type--donation-qr img');
  }

}
