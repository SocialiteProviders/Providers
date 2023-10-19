<?php

declare(strict_types=1);

namespace SocialiteProviders\Apple;

use Lcobucci\JWT\Signer\Key;

final class AppleSignerInMemory implements Key
{
    public string $contents;

    public string $passphrase;

    /** @param  non-empty-string  $contents */
    private function __construct(string $contents, string $passphrase)
    {
        $this->passphrase = $passphrase;
        $this->contents = $contents;
    }

    /** @param  non-empty-string  $contents */
    public static function plainText(string $contents, string $passphrase = ''): self
    {
        return new self($contents, $passphrase);
    }

    public function contents(): string
    {
        return $this->contents;
    }

    public function passphrase(): string
    {
        return $this->passphrase;
    }
}
