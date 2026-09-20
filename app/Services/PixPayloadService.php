<?php
// Master Arena SaaS - PixPayloadService (EMVCo QRCPS PIX Engine)
// Comments strictly in ASCII only.

namespace App\Services;

class PixPayloadService
{
    // Tag IDs defined by EMVCo and Banco Central do Brasil
    private const ID_PAYLOAD_FORMAT_INDICATOR = '00';
    private const ID_POINT_OF_INITIATION_METHOD = '01';
    private const ID_MERCHANT_ACCOUNT_INFORMATION = '26';
    private const ID_MERCHANT_ACCOUNT_INFORMATION_GUI = '00';
    private const ID_MERCHANT_ACCOUNT_INFORMATION_KEY = '01';
    private const ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION = '02';
    private const ID_MERCHANT_CATEGORY_CODE = '52';
    private const ID_TRANSACTION_CURRENCY = '53';
    private const ID_TRANSACTION_AMOUNT = '54';
    private const ID_COUNTRY_CODE = '58';
    private const ID_MERCHANT_NAME = '59';
    private const ID_MERCHANT_CITY = '60';
    private const ID_ADDITIONAL_DATA_FIELD_TEMPLATE = '05';
    private const ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID = '05';
    private const ID_CRC16 = '63';

    // Generate complete PIX Copia e Cola payload with CRC16
    public function generatePayload(
        string $pixKey,
        float $amount,
        string $txid = '***',
        string $merchantName = 'MASTER ARENA',
        string $merchantCity = 'SAO PAULO',
        ?string $description = null
    ): string {
        $cleanKey = trim($pixKey);
        $cleanName = substr($this->cleanAsciiString($merchantName), 0, 25);
        $cleanCity = substr($this->cleanAsciiString($merchantCity), 0, 15);
        $cleanTxid = preg_replace('/[^a-zA-Z0-9]/', '', $txid);
        if (empty($cleanTxid)) {
            $cleanTxid = '***';
        }

        // 1. Merchant Account Information (Tag 26)
        $gui = $this->buildTlv(self::ID_MERCHANT_ACCOUNT_INFORMATION_GUI, 'br.gov.bcb.pix');
        $key = $this->buildTlv(self::ID_MERCHANT_ACCOUNT_INFORMATION_KEY, $cleanKey);
        $desc = '';
        if ($description) {
            $desc = $this->buildTlv(self::ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION, substr($this->cleanAsciiString($description), 0, 40));
        }
        $merchantAccountInfo = $this->buildTlv(self::ID_MERCHANT_ACCOUNT_INFORMATION, $gui . $key . $desc);

        // 2. Additional Data Field (Tag 05) with TXID
        $txidField = $this->buildTlv(self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID, $cleanTxid);
        $additionalDataField = $this->buildTlv(self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE, $txidField);

        // 3. Assemble full payload before CRC
        $payload = 
            $this->buildTlv(self::ID_PAYLOAD_FORMAT_INDICATOR, '01') .
            $this->buildTlv(self::ID_POINT_OF_INITIATION_METHOD, '12') . // 12 = Dynamic QR Code, 11 = Static
            $merchantAccountInfo .
            $this->buildTlv(self::ID_MERCHANT_CATEGORY_CODE, '0000') .
            $this->buildTlv(self::ID_TRANSACTION_CURRENCY, '986') . // 986 = BRL
            $this->buildTlv(self::ID_TRANSACTION_AMOUNT, number_format($amount, 2, '.', '')) .
            $this->buildTlv(self::ID_COUNTRY_CODE, 'BR') .
            $this->buildTlv(self::ID_MERCHANT_NAME, $cleanName) .
            $this->buildTlv(self::ID_MERCHANT_CITY, $cleanCity) .
            $additionalDataField .
            self::ID_CRC16 . '04';

        // 4. Calculate and append CRC16-CCITT
        $crc = $this->calculateCrc16($payload);

        return $payload . $crc;
    }

    // Build TLV (Type-Length-Value) element
    private function buildTlv(string $id, string $value): string
    {
        $len = str_pad((string)strlen($value), 2, '0', STR_PAD_LEFT);
        return $id . $len . $value;
    }

    // Calculate CRC16-CCITT (polynomial 0x1021, init 0xFFFF)
    public function calculateCrc16(string $payload): string
    {
        $polynomial = 0x1021;
        $crc = 0xFFFF;

        for ($i = 0; $i < strlen($payload); $i++) {
            $crc ^= (ord($payload[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ $polynomial) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    // Remove any non-ASCII or accented characters for safe banking string
    private function cleanAsciiString(string $str): string
    {
        $unaccented = preg_replace(
            ['/[áàãâä]/ui', '/[éèêë]/ui', '/[íìîï]/ui', '/[óòõôö]/ui', '/[úùûü]/ui', '/[ç]/ui', '/[ñ]/ui'],
            ['a', 'e', 'i', 'o', 'u', 'c', 'n'],
            $str
        );

        $asciiOnly = preg_replace('/[^a-zA-Z0-9\s\.\-]/', '', $unaccented);
        return trim(strtoupper($asciiOnly));
    }
}
