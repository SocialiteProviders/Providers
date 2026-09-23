<?php

namespace SocialiteProviders\Tests\Saml2;

class MetadataTest extends TestCase
{
    public function test_it_serializes_service_provider_metadata(): void
    {
        $response = $this->makeProvider($this->makeRequest())->getServiceProviderMetadata();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/samlmetadata+xml', $response->headers->get('content-type'));

        $xml = $response->getContent();
        $this->assertStringContainsString('EntityDescriptor', $xml);
        $this->assertStringContainsString('AssertionConsumerService', $xml);
        $this->assertStringContainsString(static::ROOT_URL.'/'.static::ACS_ROUTE, $xml);
    }

    public function test_it_signs_metadata_when_a_service_provider_credential_is_configured(): void
    {
        $response = $this->makeProvider($this->makeRequest(), [
            'sp_certificate' => $this->spCertificatePem(),
            'sp_private_key' => $this->spPrivateKeyPem(),
        ])->getServiceProviderMetadata();

        $xml = $response->getContent();
        $this->assertStringContainsString('Signature', $xml);
        $this->assertStringContainsString('KeyDescriptor', $xml);
    }
}
