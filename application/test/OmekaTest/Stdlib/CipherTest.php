<?php declare(strict_types=1);

namespace OmekaTest\Stdlib;

use Omeka\Stdlib\Cipher;
use Omeka\Test\TestCase;

class CipherTest extends TestCase
{
    private function key($byte = "\x01")
    {
        return base64_encode(str_repeat($byte, SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
    }

    public function testRoundTrip()
    {
        $cipher = new Cipher($this->key());
        $this->assertTrue($cipher->isEnabled());
        $encrypted = $cipher->encrypt('secret-value');
        $this->assertStringStartsWith(Cipher::PREFIX, $encrypted);
        $this->assertSame('secret-value', $cipher->decrypt($encrypted));
    }

    public function testDecryptsAValueEncryptedWithAPreviousKey()
    {
        $old = $this->key("\x01");
        $new = $this->key("\x02");
        $encrypted = (new Cipher($old))->encrypt('secret-value');
        // Current key first, previous key next: the old value is still
        // readable.
        $this->assertSame('secret-value', (new Cipher([$new, $old]))->decrypt($encrypted));
    }

    public function testEncryptsWithTheCurrentKey()
    {
        $old = $this->key("\x01");
        $new = $this->key("\x02");
        $encrypted = (new Cipher([$new, $old]))->encrypt('secret-value');
        // Encrypted with the current key: readable by it, not by the old one.
        $this->assertSame('secret-value', (new Cipher($new))->decrypt($encrypted));
        $this->assertSame('', (new Cipher($old))->decrypt($encrypted));
    }

    public function testEncryptionIsNonDeterministic()
    {
        $cipher = new Cipher($this->key());
        $this->assertNotSame($cipher->encrypt('x'), $cipher->encrypt('x'));
    }

    public function testEncryptIsIdempotentOnCiphertext()
    {
        $cipher = new Cipher($this->key());
        $encrypted = $cipher->encrypt('x');
        $this->assertSame($encrypted, $cipher->encrypt($encrypted));
    }

    public function testEmptyValueIsNotEncrypted()
    {
        $cipher = new Cipher($this->key());
        $this->assertSame('', $cipher->encrypt(''));
    }

    public function testLegacyClearValueIsReturnedAsIs()
    {
        $cipher = new Cipher($this->key());
        $this->assertSame('plain-legacy', $cipher->decrypt('plain-legacy'));
    }

    /**
     * @dataProvider invalidKeyProvider
     */
    public function testDisabledWithoutValidKey($invalidKey)
    {
        $cipher = new Cipher($invalidKey);
        $this->assertFalse($cipher->isEnabled());
        $this->assertSame('x', $cipher->encrypt('x'));
        $this->assertSame('plain', $cipher->decrypt('plain'));
    }

    public function invalidKeyProvider()
    {
        return [
            'null' => [null],
            'empty' => [''],
            'not base64' => ['not base64 !!!'],
            'too short' => [base64_encode('short')],
        ];
    }

    public function testDisabledCipherCannotReadCiphertext()
    {
        $encrypted = (new Cipher($this->key()))->encrypt('x');
        $this->assertSame('', (new Cipher(null))->decrypt($encrypted));
    }

    public function testWrongKeyFailsToDecrypt()
    {
        $encrypted = (new Cipher($this->key("\x01")))->encrypt('x');
        $this->assertSame('', (new Cipher($this->key("\x02")))->decrypt($encrypted));
    }

    public function testTamperedCiphertextFailsToDecrypt()
    {
        $cipher = new Cipher($this->key());
        $this->assertSame('', $cipher->decrypt(Cipher::PREFIX . base64_encode('too-short')));
        $this->assertSame('', $cipher->decrypt(Cipher::PREFIX . 'not-base64-!!'));
    }
}
