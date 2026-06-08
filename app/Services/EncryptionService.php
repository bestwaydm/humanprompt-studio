<?php

declare(strict_types=1);

final class EncryptionService
{
    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = trim($secret);
    }

    public function isReady(): bool
    {
        return $this->secret !== '' && strlen($this->secret) >= 32;
    }

    public function encrypt(string $plainText): string
    {
        if (!$this->isReady()) {
            throw new RuntimeException('Encryption secret is missing or too short. Set HUMANPROMPT_SECRET_KEY in .env with at least 32 characters.');
        }

        if (function_exists('sodium_crypto_secretbox')) {
            $key = hash('sha256', $this->secret, true);
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $cipher = sodium_crypto_secretbox($plainText, $nonce, $key);
            return 'sodium:' . base64_encode($nonce . $cipher);
        }

        if (!function_exists('openssl_encrypt')) {
            throw new RuntimeException('No supported encryption extension found. Enable Sodium or OpenSSL.');
        }

        $key = hash('sha256', $this->secret, true);
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plainText, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            throw new RuntimeException('Encryption failed.');
        }

        return 'aes-256-gcm:' . base64_encode($iv . $tag . $cipher);
    }

    public function decrypt(string $payload): string
    {
        if (!$this->isReady()) {
            throw new RuntimeException('Encryption secret is missing or too short.');
        }

        if (str_starts_with($payload, 'sodium:')) {
            if (!function_exists('sodium_crypto_secretbox_open')) {
                throw new RuntimeException('Sodium is not available on this server.');
            }

            $raw = base64_decode(substr($payload, 7), true);
            if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
                throw new RuntimeException('Invalid encrypted payload.');
            }

            $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $key = hash('sha256', $this->secret, true);
            $plain = sodium_crypto_secretbox_open($cipher, $nonce, $key);

            if ($plain === false) {
                throw new RuntimeException('Decryption failed.');
            }

            return $plain;
        }

        if (str_starts_with($payload, 'aes-256-gcm:')) {
            if (!function_exists('openssl_decrypt')) {
                throw new RuntimeException('OpenSSL is not available on this server.');
            }

            $raw = base64_decode(substr($payload, 12), true);
            if ($raw === false || strlen($raw) <= 28) {
                throw new RuntimeException('Invalid encrypted payload.');
            }

            $iv = substr($raw, 0, 12);
            $tag = substr($raw, 12, 16);
            $cipher = substr($raw, 28);
            $key = hash('sha256', $this->secret, true);
            $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

            if ($plain === false) {
                throw new RuntimeException('Decryption failed.');
            }

            return $plain;
        }

        throw new RuntimeException('Unsupported encrypted payload format.');
    }

    public static function keyHint(string $apiKey): string
    {
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            return '';
        }

        $last = substr($apiKey, -4);
        return '••••••••' . $last;
    }
}
