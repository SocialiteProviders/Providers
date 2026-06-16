<?php

namespace SocialiteProviders\Saml2\Tests;

use Illuminate\Http\Request;
use LightSaml\Credential\KeyHelper;
use LightSaml\Credential\X509Certificate;
use LightSaml\Error\LightSamlSecurityException;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Metadata\Metadata;
use SocialiteProviders\Manager\Contracts\ConfigInterface;
use SocialiteProviders\Manager\Exception\MissingConfigException;
use SocialiteProviders\Saml2\Provider;

class ProviderCertificateRotationTest extends TestCase
{
    use GeneratesKeypairs;

    public function test_metadata_publishes_single_certificate_without_overlap_config(): void
    {
        $active = $this->generateKeypair();
        $provider = $this->makeProvider([
            'sp_certificate' => $active['certificate'],
            'sp_private_key' => $active['private_key'],
            'sp_entityid'    => 'https://example.test/sp',
            'sp_acs'         => 'auth/callback',
        ]);

        $metadata = $provider->getServiceProviderMetadata()->getContent();

        $this->assertSame(1, substr_count($metadata, 'KeyDescriptor use="signing"'));
        $this->assertStringContainsString($this->certificateBody($active['certificate']), $metadata);
        $this->assertCount(1, $provider->getServiceProviderCertificates());
    }

    public function test_metadata_publishes_active_and_previous_certificates_during_overlap(): void
    {
        $active = $this->generateKeypair();
        $previous = $this->generateKeypair();
        $provider = $this->makeProvider([
            'sp_certificate'          => $active['certificate'],
            'sp_private_key'          => $active['private_key'],
            'sp_previous_certificate' => $previous['certificate'],
            'sp_previous_private_key' => $previous['private_key'],
            'sp_entityid'             => 'https://example.test/sp',
            'sp_acs'                  => 'auth/callback',
        ]);

        $metadata = $provider->getServiceProviderMetadata()->getContent();

        $this->assertSame(2, substr_count($metadata, 'KeyDescriptor use="signing"'));
        $this->assertStringContainsString($this->certificateBody($active['certificate']), $metadata);
        $this->assertStringContainsString($this->certificateBody($previous['certificate']), $metadata);
        $this->assertCount(2, $provider->getServiceProviderCertificates());
    }

    public function test_outbound_messages_are_signed_with_active_certificate_only(): void
    {
        $active = $this->generateKeypair();
        $previous = $this->generateKeypair();
        $provider = $this->makeProvider([
            'sp_certificate'          => $active['certificate'],
            'sp_private_key'          => $active['private_key'],
            'sp_previous_certificate' => $previous['certificate'],
            'sp_previous_private_key' => $previous['private_key'],
            'sp_entityid'             => 'https://example.test/sp',
            'sp_acs'                  => 'auth/callback',
        ]);

        $metadata = $provider->getServiceProviderMetadata()->getContent();
        $entityDescriptor = Metadata::fromXML($metadata, new DeserializationContext);
        $signatureReader = $entityDescriptor->getSignature();

        $this->assertNotNull($signatureReader);

        $activeKey = KeyHelper::createPublicKey((new X509Certificate)->loadPem($active['certificate']));
        $previousKey = KeyHelper::createPublicKey((new X509Certificate)->loadPem($previous['certificate']));

        $this->assertTrue($signatureReader->validate($activeKey));

        try {
            $signatureReader->validate($previousKey);
            $this->fail('Expected metadata signature not to validate with the previous certificate.');
        } catch (LightSamlSecurityException) {
            $this->assertTrue(true);
        }
    }

    public function test_requires_both_previous_certificate_and_private_key(): void
    {
        $active = $this->generateKeypair();
        $previous = $this->generateKeypair();
        $provider = $this->makeProvider([
            'sp_certificate'          => $active['certificate'],
            'sp_private_key'          => $active['private_key'],
            'sp_previous_certificate' => $previous['certificate'],
            'sp_entityid'             => 'https://example.test/sp',
            'sp_acs'                  => 'auth/callback',
        ]);

        $this->expectException(MissingConfigException::class);
        $this->expectExceptionMessage('sp_previous_certificate');

        $provider->getServiceProviderMetadata();
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function makeProvider(array $config): Provider
    {
        $provider = new Provider(Request::create('/'));
        $provider->setConfig(new readonly class($config) implements ConfigInterface
        {
            public function __construct(private array $config) {}

            public function get(): array
            {
                return $this->config;
            }
        });

        return $provider;
    }

    protected function certificateBody(string $certificate): string
    {
        return preg_replace('/\s+/', '', (new X509Certificate)->loadPem($certificate)->getData());
    }
}
