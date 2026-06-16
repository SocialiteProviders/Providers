<?php

namespace SocialiteProviders\Saml2\Tests;

trait GeneratesKeypairs
{
    /**
     * @return array{certificate: string, private_key: string}
     */
    protected function generateKeypair(): array
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $csr = openssl_csr_new(['CN' => 'saml-test'], $key, ['digest_alg' => 'sha256']);
        $certificate = openssl_csr_sign($csr, null, $key, 365, ['digest_alg' => 'sha256']);

        openssl_x509_export($certificate, $certificatePem);
        openssl_pkey_export($key, $privateKeyPem);

        return [
            'certificate' => $certificatePem,
            'private_key' => $privateKeyPem,
        ];
    }
}
