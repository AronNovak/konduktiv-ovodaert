<?php

declare(strict_types=1);

namespace Drupal\petoovoda_donation;

/**
 * Builds an MNB instant payment (qvik) HCT QR code payload.
 *
 * The payload follows the MNB QR code standard for instant payments:
 * 17 fields, each terminated by an LF character, UTF-8 encoded,
 * with a total maximum size of 345 bytes. Scanning the resulting
 * QR code in a Hungarian mobile banking app opens a pre-filled
 * credit transfer form.
 *
 * Verify field order and formats against the official specification
 * PDF available on the MNB qvik portal before going live.
 */
final class HctQrPayloadBuilder {

  /**
   * Maximum payload size in bytes as defined by the MNB standard.
   */
  private const MAX_BYTES = 345;

  /**
   * Builds the HCT QR payload string.
   *
   * @param string $bic
   *   The beneficiary bank's BIC code (8 or 11 characters).
   * @param string $name
   *   The beneficiary (foundation) name, max 70 characters.
   * @param string $iban
   *   The beneficiary IBAN (HUxx..., 28 characters, no spaces).
   * @param int|null $amount
   *   Optional amount in HUF. NULL lets the donor enter any amount.
   * @param string $message
   *   Optional remittance info shown in the transfer, max 70 characters.
   * @param int $validity_seconds
   *   Validity window from now. Keep it short and make sure the page
   *   or image is not cached longer than this window.
   *
   * @return string
   *   The payload to encode into the QR code.
   *
   * @throws \InvalidArgumentException
   *   If a mandatory field is invalid or the payload exceeds 345 bytes.
   */
  public static function build(string $bic, string $name, string $iban, ?int $amount = NULL, string $message = '', int $validity_seconds = 3600): string {
    $iban = strtoupper(str_replace(' ', '', $iban));

    if (!preg_match('/^HU\d{26}$/', $iban)) {
      throw new \InvalidArgumentException('Invalid Hungarian IBAN.');
    }
    if (!preg_match('/^[A-Z0-9]{8}([A-Z0-9]{3})?$/', strtoupper($bic))) {
      throw new \InvalidArgumentException('Invalid BIC code.');
    }
    if (mb_strlen($name) > 70 || $name === '') {
      throw new \InvalidArgumentException('Beneficiary name must be 1-70 characters.');
    }
    if ($amount !== NULL && ($amount < 1 || $amount > 999999999999)) {
      throw new \InvalidArgumentException('Amount out of range.');
    }

    // Validity in local time with UTC offset in hours, e.g.
    // "20260708153000+2".
    $expires = new \DateTimeImmutable('@' . (time() + $validity_seconds));
    $expires = $expires->setTimezone(new \DateTimeZone('Europe/Budapest'));
    $offset_hours = (int) ($expires->getOffset() / 3600);
    $validity = $expires->format('YmdHis') . sprintf('%+d', $offset_hours);

    $fields = [
      // 1. Identification code: HCT = the scanner initiates the transfer.
      'HCT',
      // 2. Version.
      '001',
      // 3. Character set: 1 = UTF-8.
      '1',
      // 4. Beneficiary BIC.
      strtoupper($bic),
      // 5. Beneficiary name.
      $name,
      // 6. Beneficiary IBAN.
      $iban,
      // 7. Amount, optional: "HUF" prefix + integer forint value.
      $amount === NULL ? '' : 'HUF' . $amount,
      // 8. Validity, mandatory.
      $validity,
      // 9. Payment situation identifier, optional.
      '',
      // 10. Remittance information, optional.
      mb_substr($message, 0, 70),
      // 11-17. Shop ID, merchant device, invoice ID, customer ID,
      // beneficiary internal transaction ID, loyalty ID, NAV code.
      '', '', '', '', '', '', '',
    ];

    $payload = implode("\n", $fields) . "\n";

    if (strlen($payload) > self::MAX_BYTES) {
      throw new \InvalidArgumentException('Payload exceeds 345 bytes, shorten the message.');
    }

    return $payload;
  }

}
