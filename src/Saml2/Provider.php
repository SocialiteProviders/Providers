<?php

namespace SocialiteProviders\Saml2;

use DateTime;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use LightSaml\Binding\BindingFactory;
use LightSaml\Builder\EntityDescriptor\SimpleEntityDescriptorBuilder;
use LightSaml\ClaimTypes;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Credential\KeyHelper;
use LightSaml\Credential\X509Certificate;
use LightSaml\Credential\X509Credential;
use LightSaml\Error\LightSamlSecurityException;
use LightSaml\Helper;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Context\SerializationContext;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\Metadata\Metadata;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\Model\Protocol\AuthnRequest;
use LightSaml\Model\Protocol\NameIDPolicy;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\Model\XmlDSig\SignatureXmlReader;
use LightSaml\SamlConstants;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SocialiteProviders\Manager\Contracts\ConfigInterface;
use SocialiteProviders\Manager\Exception\MissingConfigException;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class Provider extends AbstractProvider implements SocialiteProvider
{
    /**
     * The HTTP request instance.
     *
     * @var Request
     */
    protected $request;

    /**
     * The received SAML2 message's context.
     *
     * @var MessageContext
     */
    protected $messageContext;

    /**
     * The SAML2 User instance.
     *
     * @var User
     */
    protected $user;

    /**
     * The configuration.
     *
     * @var array
     */
    protected $config;

    public const CACHE_KEY = 'socialite_saml2_metadata';
    public const CACHE_KEY_TTL = self::CACHE_KEY.'_ttl';

    public function __construct(Request $request)
    {
        parent::__construct($request, '', '', '');
        $this->messageContext = new MessageContext();
    }

    public function setConfig(ConfigInterface $config): Provider
    {
        $config = $config->get();

        $this->config = $config;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys(): array
    {
        return [
            'metadata',
            'ttl',
            'acs',
            'entityid',
            'certificate',
            'sp_acs',
            'sp_entityid',
            'sp_certificate',
            'sp_private_key',
            'sp_private_key_passphrase',
            'idp_binding_method',
        ];
    }

    protected function getConfig($key = null, $default = null)
    {
        if (!empty($key) && empty($this->config[$key])) {
            return $default;
        }

        return $key ? Arr::get($this->config, $key, $default) : $this->config;
    }

    public function redirect()
    {
        $this->request->session()->put('state', $state = $this->getState());

        $bindingType = $this->getConfig('idp_binding_method', SamlConstants::BINDING_SAML2_HTTP_REDIRECT);

        $identityProviderConsumerService = $this->getIdentityProviderEntityDescriptor()
            ->getFirstIdpSsoDescriptor()
            ->getFirstSingleSignOnService($bindingType);

        $authnRequest = new AuthnRequest();
        $authnRequest
            ->setID(Helper::generateID())
            ->setProtocolBinding($this->getAssertionConsumerServiceBinding())
            ->setRelayState($state)
            ->setIssueInstant(new DateTime())
            ->setDestination($identityProviderConsumerService->getLocation())
            ->setNameIDPolicy((new NameIDPolicy())->setFormat(SamlConstants::NAME_ID_FORMAT_PERSISTENT))
            ->setIssuer(new Issuer($this->getServiceProviderEntityDescriptor()->getEntityID()));

        return $this->sendMessage($authnRequest, $identityProviderConsumerService->getBinding());
    }

    protected function sendMessage(SamlMessage $message, string $bindingType): HttpFoundationResponse
    {
        if ($credential = $this->credential()) {
            $message->setSignature($this->signature($credential));
        }

        $messageContext = new MessageContext();
        $messageContext->setMessage($message);

        $binding = (new BindingFactory())->create($bindingType);

        return $binding->send($messageContext);
    }

    protected function getIdentityProviderEntityDescriptorManually(): EntityDescriptor
    {
        $acs = $this->getConfig('acs');
        $entityId = $this->getConfig('entityid');
        $certificate = $this->getConfig('certificate');

        if (!$entityId || !$certificate) {
            throw new MissingConfigException('When using "acs", both "entityid" and "certificate" must be set');
        }

        $x509 = $this->makeCertificate($certificate);

        $builder = new SimpleEntityDescriptorBuilder($entityId, $acs, $acs, $x509);

        return $builder->get();
    }

    protected function getIdentityProviderEntityDescriptorFromXml(): EntityDescriptor
    {
        return Metadata::fromXML($this->getConfig('metadata'), new DeserializationContext());
    }

    protected function getIdentityProviderEntityDescriptorFromUrl(): EntityDescriptor
    {
        $metadataUrl = $this->getConfig('metadata');
        $xml = Cache::get(self::CACHE_KEY);
        $ttl = Cache::get(self::CACHE_KEY_TTL);

        $deserializationContext = new DeserializationContext();

        if ($xml && $ttl && $ttl + $this->getConfig('ttl', 86400) > time()) {
            return Metadata::fromXML($xml, $deserializationContext);
        }

        Cache::forever(self::CACHE_KEY_TTL, time());

        try {
            $xml = $this->getHttpClient()
                ->get($metadataUrl)
                ->getBody()
                ->getContents();

            Cache::forever(self::CACHE_KEY, $xml);
        } catch (GuzzleException $e) {
            if (!$xml) {
                throw $e;
            }
        }

        return Metadata::fromXML($xml, $deserializationContext);
    }

    /**
     * @throws MissingConfigException
     * @throws GuzzleException
     */
    public function getIdentityProviderEntityDescriptor(): EntityDescriptor
    {
        if ($this->getConfig('acs')) {
            return $this->getIdentityProviderEntityDescriptorManually();
        }

        $metadata = $this->getConfig('metadata');
        if ($metadata) {
            if (!Validator::make(['u' => $metadata], ['u' => 'url'])->fails()) {
                return $this->getIdentityProviderEntityDescriptorFromUrl();
            } else {
                return $this->getIdentityProviderEntityDescriptorFromXml();
            }
        }

        throw new MissingConfigException('Either the "metadata" or "acs" config keys must be set');
    }

    public function getServiceProviderEntityDescriptor(): EntityDescriptor
    {
        $entityDescriptor = new EntityDescriptor();
        $entityDescriptor
            ->setEntityID($this->getConfig('sp_entityid', URL::to('auth/saml2')))
            ->addItem(
                (new SpSsoDescriptor())
                    ->setWantAssertionsSigned(true)
                    ->addAssertionConsumerService(
                        (new AssertionConsumerService())
                            ->setBinding($this->getAssertionConsumerServiceBinding())
                            ->setLocation(URL::to($this->getAssertionConsumerServiceRoute()))
                    )
            );

        return $entityDescriptor;
    }

    public function getServiceProviderAssertionConsumerUrl(): string
    {
        return $this->getServiceProviderEntityDescriptor()
            ->getFirstSpSsoDescriptor()
            ->getFirstAssertionConsumerService()
            ->getLocation();
    }

    public function getServiceProviderEntityId(): string
    {
        return $this->getServiceProviderEntityDescriptor()
            ->getEntityID();
    }

    protected function getAssertionConsumerServiceRoute(): string
    {
        return Str::of($this->getConfig('sp_acs', 'auth/callback'))->ltrim('/');
    }

    protected function getAssertionConsumerServiceBinding(): string
    {
        return Arr::has(
            Route::getRoutes()->getRoutesByMethod(),
            'GET.'.$this->getAssertionConsumerServiceRoute()
        ) ?
            SamlConstants::BINDING_SAML2_HTTP_REDIRECT :
            SamlConstants::BINDING_SAML2_HTTP_POST;
    }

    /**
     * @return SocialiteUser|User
     */
    public function user()
    {
        if ($this->user) {
            return $this->user;
        }

        $this->receive();

        if ($this->hasInvalidState()) {
            throw new InvalidStateException();
        }

        $this->decryptAssertions();

        if ($this->hasInvalidSignature()) {
            throw new InvalidSignatureException();
        }

        $assertion = $this->messageContext->asResponse()->getFirstAssertion();
        $attributeStatement = $assertion->getFirstAttributeStatement();

        $this->user = new User();
        $this->user->setAssertion($assertion);
        $this->user->map(['id' => $assertion->getSubject()->getNameID()->getValue()]);

        if ($attributeStatement) {
            foreach (['name' => ClaimTypes::NAME, 'email' => ClaimTypes::EMAIL_ADDRESS] as $key => $claim) {
                if ($attribute = $attributeStatement->getFirstAttributeByName($claim)) {
                    $this->user->map([$key => $attribute->getFirstAttributeValue()]);
                }
            }

            $this->user->setRaw($attributeStatement->getAllAttributes());
        }

        return $this->user;
    }

    protected function hasInvalidState(): bool
    {
        if ($this->isStateless()) {
            return false;
        }

        $state = $this->request->session()->pull('state');

        return !(strlen($state) > 0 && $this->messageContext->getMessage()->getRelayState() === $state);
    }

    protected function hasInvalidSignature(): bool
    {
        $keyDescriptors = $this->getIdentityProviderEntityDescriptor()
            ->getFirstIdpSsoDescriptor()
            ->getAllKeyDescriptorsByUse(KeyDescriptor::USE_SIGNING);

        /** @var SignatureXmlReader $signatureReader */
        $signatureReader = SamlConstants::BINDING_SAML2_HTTP_REDIRECT === $this->messageContext->getBindingType()
            ? $this->messageContext->getMessage()->getSignature()
            : $this->messageContext->asResponse()->getFirstAssertion()->getSignature();

        if (!$signatureReader) {
            return true;
        }

        foreach ($keyDescriptors as $keyDescriptor) {
            $key = KeyHelper::createPublicKey($keyDescriptor->getCertificate());

            try {
                if ($signatureReader->validate($key)) {
                    return false;
                }
            } catch (LightSamlSecurityException $e) {
                continue;
            }
        }

        return true;
    }

    protected function receive(): void
    {
        $bindingFactory = new BindingFactory();
        $bindingType = $bindingFactory->detectBindingType($this->request);
        $bindingFactory->create($bindingType)->receive($this->request, $this->messageContext);
        $this->messageContext->setBindingType($bindingType);
    }

    protected function decryptAssertions(): void
    {
        $credential = $this->credential();
        if (null === $credential) {
            return;
        }

        /** @var \LightSaml\Model\Assertion\EncryptedAssertionReader $reader */
        $reader = $this->messageContext->asResponse()->getFirstEncryptedAssertion();

        if (null === $reader) {
            return;
        }

        $assertion = $reader->decryptMultiAssertion([$credential], new DeserializationContext());
        $this->messageContext->asResponse()->addAssertion($assertion);
    }

    public function getServiceProviderMetadata(): Response
    {
        $entityDescriptor = $this->getServiceProviderEntityDescriptor();
        $serializationContext = new SerializationContext();
        $entityDescriptor->serialize($serializationContext->getDocument(), $serializationContext);

        return (new Response())
            ->header('content-type', 'application/samlmetadata+xml')
            ->setContent($serializationContext->getDocument()->saveXML());
    }

    public function clearIdentityProviderMetadataCache()
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY_TTL);
    }

    protected function signature(X509Credential $credential): SignatureWriter
    {
        return new SignatureWriter(
            $credential->getCertificate(),
            $credential->getPrivateKey(),
            XMLSecurityDSig::SHA256
        );
    }

    protected function credential(): ?X509Credential
    {
        if (!$this->getConfig('sp_certificate') || !$this->getConfig('sp_private_key')) {
            return null;
        }

        return new X509Credential(
            $this->makeCertificate($this->getConfig('sp_certificate')),
            KeyHelper::createPrivateKey(
                $this->getConfig('sp_private_key'),
                $this->getConfig('sp_private_key_passphrase'),
                false,
                XMLSecurityKey::RSA_SHA256
            )
        );
    }

    protected function makeCertificate(?string $data): X509Certificate
    {
        $cert = new X509Certificate();

        if (null === $data) {
            return $cert;
        }

        /**
         * The SAML certificate may be provided as either a properly formatted certificate with header and line breaks
         * or as a string containing only the body.
         */
        if (Str::startsWith($data, '-----BEGIN CERTIFICATE-----')) {
            return $cert->loadPem($data);
        }

        return $cert->setData($data);
    }

    protected function getTokenUrl()
    {
        throw new NotSupportedException();
    }

    protected function getAuthUrl($state)
    {
        throw new NotSupportedException();
    }

    protected function getUserByToken($token)
    {
        throw new NotSupportedException();
    }

    protected function mapUserToObject(array $user)
    {
        throw new NotSupportedException();
    }
}
