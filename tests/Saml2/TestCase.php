<?php

namespace SocialiteProviders\Tests\Saml2;

use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use LightSaml\ClaimTypes;
use LightSaml\Context\Model\SerializationContext;
use LightSaml\Credential\KeyHelper;
use LightSaml\Credential\X509Certificate;
use LightSaml\Helper;
use LightSaml\Model\Assertion\Assertion;
use LightSaml\Model\Assertion\Attribute;
use LightSaml\Model\Assertion\AttributeStatement;
use LightSaml\Model\Assertion\Conditions;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Assertion\NameID;
use LightSaml\Model\Assertion\Subject;
use LightSaml\Model\Assertion\SubjectConfirmation;
use LightSaml\Model\Assertion\SubjectConfirmationData;
use LightSaml\Model\Protocol\Response as SamlResponse;
use LightSaml\Model\Protocol\Status;
use LightSaml\Model\Protocol\StatusCode;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\SamlConstants;
use Orchestra\Testbench\TestCase as Orchestra;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\Manager\ServiceProvider;
use SocialiteProviders\Saml2\Provider;

abstract class TestCase extends Orchestra
{
    protected const IDP_ENTITY_ID = 'https://idp.test/metadata';

    protected const IDP_SSO_URL = 'https://idp.test/sso';

    protected const IDP_SLO_URL = 'https://idp.test/slo';

    protected const ROOT_URL = 'https://sp.test';

    protected const ACS_ROUTE = 'auth/callback';

    protected const SLS_ROUTE = 'auth/logout';

    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Recipient validation matches URL::to() endpoints, so pin a root the fixtures can target.
        config(['app.url' => static::ROOT_URL]);
        URL::forceRootUrl(static::ROOT_URL);
        URL::forceScheme('https');

        // The SP advertises only bindings whose routes exist, so register the ACS/SLS endpoints.
        Route::get(static::ACS_ROUTE, fn () => 'acs');
        Route::post(static::ACS_ROUTE, fn () => 'acs');
        Route::get(static::SLS_ROUTE, fn () => 'sls');
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function makeProvider(Request $request, array $config = []): Provider
    {
        $provider = new Provider($request);

        $provider->setConfig(new Config('client-id', 'client-secret', static::ROOT_URL.'/'.static::ACS_ROUTE, array_merge([
            'acs'         => static::IDP_SSO_URL,
            'entityid'    => static::IDP_ENTITY_ID,
            'certificate' => $this->idpCertificateBody(),
        ], $config)));

        return $provider;
    }

    protected function makeRequest(string $method = 'GET', array $parameters = [], ?string $uri = null): Request
    {
        $request = Request::create($uri ?? static::ROOT_URL.'/'.static::ACS_ROUTE, $method, $parameters);

        $store = new Store('saml2-test', new ArraySessionHandler(120));
        $store->start();
        $request->setLaravelSession($store);

        return $request;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @param  array<string, string>  $attributes
     */
    protected function samlResponse(array $overrides = [], array $attributes = []): string
    {
        return base64_encode($this->samlResponseXml($overrides, $attributes));
    }

    /**
     * Timestamps are built fresh so the provider's time-restriction validation passes.
     *
     * @param  array<string, mixed>  $overrides
     * @param  array<string, string>  $attributes
     */
    protected function samlResponseXml(array $overrides = [], array $attributes = []): string
    {
        $recipient = $overrides['recipient'] ?? static::ROOT_URL.'/'.static::ACS_ROUTE;
        $issuer = $overrides['issuer'] ?? static::IDP_ENTITY_ID;
        $nameId = $overrides['nameId'] ?? 'user@example.com';
        $sign = $overrides['sign'] ?? true;

        $attributes = $attributes ?: [
            ClaimTypes::EMAIL_ADDRESS => 'user@example.com',
            ClaimTypes::COMMON_NAME   => 'Test User',
            ClaimTypes::GIVEN_NAME    => 'Test',
            ClaimTypes::SURNAME       => 'User',
        ];

        $now = new DateTime('now', new DateTimeZone('UTC'));
        $notOnOrAfter = (clone $now)->modify('+1 hour');

        $attributeStatement = new AttributeStatement;
        foreach ($attributes as $name => $value) {
            $attributeStatement->addAttribute(new Attribute($name, $value));
        }

        $assertion = new Assertion;
        $assertion
            ->setId(Helper::generateID())
            ->setIssueInstant($now)
            ->setIssuer(new Issuer($issuer))
            ->setSubject(
                (new Subject)
                    ->setNameID(new NameID($nameId, SamlConstants::NAME_ID_FORMAT_EMAIL))
                    ->addSubjectConfirmation(
                        (new SubjectConfirmation)
                            ->setMethod(SamlConstants::CONFIRMATION_METHOD_BEARER)
                            ->setSubjectConfirmationData(
                                (new SubjectConfirmationData)
                                    ->setNotOnOrAfter($notOnOrAfter->getTimestamp())
                                    ->setRecipient($recipient)
                            )
                    )
            )
            ->setConditions(
                (new Conditions)
                    ->setNotBefore($now->getTimestamp())
                    ->setNotOnOrAfter($notOnOrAfter->getTimestamp())
            )
            ->addItem($attributeStatement);

        if ($sign) {
            $assertion->setSignature(new SignatureWriter($this->idpCertificate(), $this->idpPrivateKey(), XMLSecurityDSig::SHA256));
        }

        $response = new SamlResponse;
        $response
            ->setId(Helper::generateID())
            ->setIssueInstant($now)
            ->setIssuer(new Issuer($issuer))
            ->setStatus(new Status(new StatusCode($overrides['status'] ?? SamlConstants::STATUS_SUCCESS)))
            ->setDestination($recipient)
            ->addAssertion($assertion);

        $context = new SerializationContext;
        $response->serialize($context->getDocument(), $context);

        return $context->getDocument()->saveXML();
    }

    protected function idpCertificate(): X509Certificate
    {
        return (new X509Certificate)->loadPem($this->fixture('idp.crt'));
    }

    protected function idpPrivateKey(): XMLSecurityKey
    {
        return KeyHelper::createPrivateKey($this->fixture('idp.key'), '', false, XMLSecurityKey::RSA_SHA256);
    }

    protected function idpCertificateBody(): string
    {
        return trim(preg_replace('/-----[^-]+-----|\s+/', '', $this->fixture('idp.crt')));
    }

    protected function spCertificatePem(): string
    {
        return $this->fixture('idp.crt');
    }

    protected function spPrivateKeyPem(): string
    {
        return $this->fixture('idp.key');
    }

    protected function fixture(string $name): string
    {
        return file_get_contents(__DIR__.'/fixtures/'.$name);
    }
}
