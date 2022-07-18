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
use LightSaml\Model\Assertion\AttributeStatement;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Context\SerializationContext;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\ContactPerson;
use LightSaml\Model\Metadata\EntitiesDescriptor;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\Metadata\Metadata;
use LightSaml\Model\Metadata\Organization;
use LightSaml\Model\Metadata\SingleLogoutService;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\Model\Protocol\AuthnRequest;
use LightSaml\Model\Protocol\LogoutResponse;
use LightSaml\Model\Protocol\NameIDPolicy;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Model\Protocol\Status;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\Model\XmlDSig\SignatureXmlReader;
use LightSaml\SamlConstants;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RuntimeException;
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

    public const ATTRIBUTE_MAP = [
        'email' => [
            ClaimTypes::EMAIL_ADDRESS,
            OasisAttributeNameUris::MAIL,
            ClaimTypes::ADFS_1_EMAIL,
        ],
        'name' => [
            ClaimTypes::NAME,
            OasisAttributeNameUris::DISPLAY_NAME,
            ClaimTypes::COMMON_NAME,
            OasisAttributeNameUris::COMMON_NAME,
        ],
        'first_name' => [
            ClaimTypes::GIVEN_NAME,
            OasisAttributeNameUris::GIVEN_NAME,
        ],
        'last_name' => [
            ClaimTypes::SURNAME,
            OasisAttributeNameUris::SURNAME,
        ],
        'upn' => [
            ClaimTypes::UPN,
            OasisAttributeNameUris::UID,
            ClaimTypes::ADFS_1_UPN,
        ],
    ];

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
            'client_id',
            'client_secret',
            'redirect',
            'metadata',
            'ttl',
            'acs',
            'entityid',
            'certificate',
            'sp_acs',
            'sp_sls',
            'sp_entityid',
            'sp_certificate',
            'sp_private_key',
            'sp_private_key_passphrase',
            'sp_tech_contact_surname',
            'sp_tech_contact_givenname',
            'sp_tech_contact_email',
            'sp_org_lang',
            'sp_org_name',
            'sp_org_display_name',
            'sp_org_url',
            'sp_default_binding_method',
            'idp_binding_method',
            'attribute_map',
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
        $bindingType = $this->getConfig('idp_binding_method', SamlConstants::BINDING_SAML2_HTTP_REDIRECT);

        $identityProviderConsumerService = $this->getIdentityProviderEntityDescriptor()
            ->getFirstIdpSsoDescriptor()
            ->getFirstSingleSignOnService($bindingType);

        $authnRequest = new AuthnRequest();
        $authnRequest
            ->setID(Helper::generateID())
            ->setProtocolBinding($this->getDefaultAssertionConsumerServiceBinding())
            ->setIssueInstant(new DateTime())
            ->setDestination($identityProviderConsumerService->getLocation())
            ->setNameIDPolicy((new NameIDPolicy())->setFormat(SamlConstants::NAME_ID_FORMAT_PERSISTENT))
            ->setIssuer(new Issuer($this->getServiceProviderEntityDescriptor()->getEntityID()));

        if ($this->usesState()) {
            $this->request->session()->put('state', $state = $this->getState());
            $authnRequest->setRelayState($state);
        }

        return $this->sendMessage($authnRequest, $identityProviderConsumerService->getBinding());
    }

    public function logoutResponse(): HttpFoundationResponse
    {
        $this->receive();

        if ($this->hasInvalidSignature()) {
            throw new InvalidSignatureException();
        }

        $bindingType = $this->getConfig('idp_binding_method', SamlConstants::BINDING_SAML2_HTTP_REDIRECT);

        $identityProviderLogoutService = $this->getIdentityProviderEntityDescriptor()
            ->getFirstIdpSsoDescriptor()
            ->getFirstSingleLogoutService($bindingType);

        $logoutResponse = new LogoutResponse();
        $logoutResponse
            ->setID(Helper::generateID())
            ->setInResponseTo($this->messageContext->getMessage()->getID())
            ->setRelayState($this->messageContext->getMessage()->getRelayState())
            ->setIssueInstant(new DateTime())
            ->setDestination($identityProviderLogoutService->getLocation())
            ->setIssuer(new Issuer($this->getServiceProviderEntityDescriptor()->getEntityID()))
            ->setStatus((new Status())->setSuccess());

        return $this->sendMessage($logoutResponse, $identityProviderLogoutService->getBinding());
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

    protected function getFirstEntityDescriptorFromXml(string $xml): EntityDescriptor
    {
        $descriptor = Metadata::fromXML($xml, new DeserializationContext());

        if ($descriptor instanceof EntitiesDescriptor) {
            return Arr::first($descriptor->getAllEntityDescriptors());
        }

        return $descriptor;
    }

    protected function getIdentityProviderEntityDescriptorFromXml(): EntityDescriptor
    {
        return $this->getFirstEntityDescriptorFromXml($this->getConfig('metadata'));
    }

    protected function getIdentityProviderEntityDescriptorFromUrl(): EntityDescriptor
    {
        $metadataUrl = $this->getConfig('metadata');
        $xml = Cache::get(self::CACHE_KEY);
        $ttl = Cache::get(self::CACHE_KEY_TTL);

        if ($xml && $ttl && $ttl + $this->getConfig('ttl', 86400) > time()) {
            return $this->getFirstEntityDescriptorFromXml($xml);
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

        return $this->getFirstEntityDescriptorFromXml($xml);
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
        $spSsoDescriptor = new SpSsoDescriptor();
        $spSsoDescriptor->setWantAssertionsSigned(true)->addNameIDFormat(SamlConstants::NAME_ID_FORMAT_PERSISTENT);

        foreach ([SamlConstants::BINDING_SAML2_HTTP_REDIRECT, SamlConstants::BINDING_SAML2_HTTP_POST] as $binding) {
            $acsRoute = $this->getAssertionConsumerServiceRoute();
            if ($this->hasRouteBindingType($acsRoute, $binding)) {
                $spSsoDescriptor->addAssertionConsumerService(
                    (new AssertionConsumerService())
                        ->setIsDefault($this->getDefaultAssertionConsumerServiceBinding() === $binding)
                        ->setBinding($binding)
                        ->setLocation(URL::to($acsRoute))
                );
            }

            $slsRoute = $this->getSingleLogoutServiceRoute();
            if ($slsRoute && $this->hasRouteBindingType($slsRoute, $binding)) {
                $spSsoDescriptor->addSingleLogoutService((new SingleLogoutService(URL::to($slsRoute), $binding)));
            }
        }

        $entityDescriptor = new EntityDescriptor();
        $entityDescriptor->setID(Helper::generateID())
            ->setEntityID($this->getConfig('sp_entityid', URL::to('auth/saml2')))
            ->addItem($spSsoDescriptor);

        if ($credential = $this->credential()) {
            $entityDescriptor->setSignature($this->signature($credential));
            $spSsoDescriptor->setAuthnRequestsSigned(true)
                ->addKeyDescriptor(new KeyDescriptor(KeyDescriptor::USE_SIGNING, $credential->getCertificate()))
                ->addKeyDescriptor(new KeyDescriptor(KeyDescriptor::USE_ENCRYPTION, $credential->getCertificate()));
        }

        if ($this->getConfig('sp_org_name')) {
            $entityDescriptor->addOrganization(
                (new Organization())->setLang($this->getConfig('sp_org_lang', 'en'))
                    ->setOrganizationDisplayName($this->getConfig('sp_org_display_name'))
                    ->setOrganizationName($this->getConfig('sp_org_name'))
                    ->setOrganizationURL($this->getConfig('sp_org_url'))
            );
        }

        if ($this->getConfig('sp_tech_contact_email')) {
            $entityDescriptor->addContactPerson(
                (new ContactPerson())->setContactType('technical')
                    ->setEmailAddress($this->getConfig('sp_tech_contact_email'))
                    ->setSurName($this->getConfig('sp_tech_contact_surname'))
                    ->setGivenName($this->getConfig('sp_tech_contact_givenname'))
            );
        }

        return $entityDescriptor;
    }

    public function getServiceProviderAssertionConsumerUrl(): string
    {
        return $this->getServiceProviderEntityDescriptor()
            ->getFirstSpSsoDescriptor()
            ->getFirstAssertionConsumerService($this->getDefaultAssertionConsumerServiceBinding())
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

    protected function getSingleLogoutServiceRoute(): string
    {
        return Str::of($this->getConfig('sp_sls'))->ltrim('/');
    }

    protected function getDefaultAssertionConsumerServiceBinding(): string
    {
        $default = $this->hasRouteBindingType(
            $this->getAssertionConsumerServiceRoute(),
            SamlConstants::BINDING_SAML2_HTTP_REDIRECT
        ) ? SamlConstants::BINDING_SAML2_HTTP_REDIRECT : SamlConstants::BINDING_SAML2_HTTP_POST;

        return $this->getConfig('sp_default_binding_method', $default);
    }

    protected function hasRouteBindingType(string $route, string $bindingType): bool
    {
        $methods = [
            SamlConstants::BINDING_SAML2_HTTP_REDIRECT => 'GET',
            SamlConstants::BINDING_SAML2_HTTP_POST     => 'POST',
        ];

        if (!array_key_exists($bindingType, $methods)) {
            return false;
        }

        return Arr::has(Route::getRoutes()->getRoutesByMethod(), $methods[$bindingType].'.'.$route);
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
        $this->ensureSuccessfulStatus();

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
            $this->user->map($this->mapAttributes($attributeStatement));
            $this->user->setRaw($attributeStatement->getAllAttributes());
        }

        return $this->user;
    }

    protected function mapAttributes(AttributeStatement $attributeStatement): array
    {
        return array_map(function ($attributeNames) use ($attributeStatement) {
            foreach (Arr::wrap($attributeNames) as $attributeName) {
                if ($attribute = $attributeStatement->getFirstAttributeByName($attributeName)) {
                    return $attribute->getFirstAttributeValue();
                }
            }

            return null;
        }, array_merge(static::ATTRIBUTE_MAP, $this->getConfig('attribute_map', [])));
    }

    protected function ensureSuccessfulStatus(): void
    {
        $status = $this->messageContext->asResponse()->getStatus();

        if (!$status->isSuccess()) {
            throw new RuntimeException('Server responded with: '.$status->getStatusCode()->getValue());
        }
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

        $assertion = $reader->decryptAssertion($credential->getPrivateKey(), new DeserializationContext());
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
