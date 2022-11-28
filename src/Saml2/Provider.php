<?php

namespace SocialiteProviders\Saml2;

use DateTime;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
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
use LightSaml\Criteria\CriteriaSet;
use LightSaml\Error\LightSamlSecurityException;
use LightSaml\Error\LightSamlValidationException;
use LightSaml\Helper;
use LightSaml\Model\Assertion\Assertion;
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
use LightSaml\Resolver\Endpoint\Criteria\DescriptorTypeCriteria;
use LightSaml\Resolver\Endpoint\Criteria\LocationCriteria;
use LightSaml\Resolver\Endpoint\Criteria\ServiceTypeCriteria;
use LightSaml\Resolver\Endpoint\DescriptorTypeEndpointResolver;
use LightSaml\SamlConstants;
use LightSaml\Validator\Model\Assertion\AssertionTimeValidator;
use LightSaml\Validator\Model\Assertion\AssertionValidator;
use LightSaml\Validator\Model\NameId\NameIdValidator;
use LightSaml\Validator\Model\Statement\StatementValidator;
use LightSaml\Validator\Model\Subject\SubjectValidator;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SocialiteProviders\Manager\Contracts\ConfigInterface;
use SocialiteProviders\Manager\Exception\MissingConfigException;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

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

    public const CACHE_NAMESPACE = 'socialite_saml2';
    public const METADATA_CACHE_KEY = self::CACHE_NAMESPACE.'_metadata';
    public const METADATA_CACHE_KEY_TTL = self::METADATA_CACHE_KEY.'_ttl';
    public const ID_CACHE_PREFIX = self::CACHE_NAMESPACE.'_id_';

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
            ->setIssuer(new Issuer($this->getServiceProviderEntityDescriptor()->getEntityID()))
            ->setAssertionConsumerServiceURL($this->getServiceProviderAssertionConsumerUrl());

        if ($this->usesState()) {
            $this->request->session()->put('state', $state = $this->getState());
            $authnRequest->setRelayState($state);
        }

        return $this->sendMessage($authnRequest, $identityProviderConsumerService->getBinding());
    }

    public function logoutResponse(): HttpFoundationResponse
    {
        $this->receive();

        $this->validateSignature();

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
        $xml = Cache::get(self::METADATA_CACHE_KEY);
        $ttl = Cache::get(self::METADATA_CACHE_KEY_TTL);

        if ($xml && $ttl && $ttl + $this->getConfig('ttl', 86400) > time()) {
            return $this->getFirstEntityDescriptorFromXml($xml);
        }

        Cache::forever(self::METADATA_CACHE_KEY_TTL, time());

        try {
            $xml = (string) $this->getHttpClient()
                ->get($metadataUrl)
                ->getBody();

            Cache::forever(self::METADATA_CACHE_KEY, $xml);
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
            SamlConstants::BINDING_SAML2_HTTP_POST
        ) ? SamlConstants::BINDING_SAML2_HTTP_POST : SamlConstants::BINDING_SAML2_HTTP_REDIRECT;

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

        try {
            Route::getRoutes()->match(Request::create($route, $methods[$bindingType]));
        } catch (MethodNotAllowedHttpException $e) {
            return false;
        }

        return true;
    }

    protected function getFirstAssertion(): Assertion
    {
        return $this->messageContext->asResponse()->getFirstAssertion();
    }

    protected function validateAssertion(): void
    {
        $assertionValidator = new AssertionValidator(new NameIdValidator(), new SubjectValidator(new NameIdValidator()), new StatementValidator());
        $assertionValidator->validateAssertion($this->getFirstAssertion());
    }

    protected function validateIssuer(): void
    {
        if ($this->getIdentityProviderEntityDescriptor()->getEntityID() !== $this->getFirstAssertion()->getIssuer()->getValue()) {
            throw new LightSamlValidationException('The assertion issuer entity id did not match the configured identity provider entity id');
        }
    }

    protected function validateRecipient(): void
    {
        $recipient = $this->getFirstAssertion()->getSubject()->getFirstSubjectConfirmation()->getSubjectConfirmationData()->getRecipient();

        $criteriaSet = new CriteriaSet([
            new DescriptorTypeCriteria(SpSsoDescriptor::class),
            new ServiceTypeCriteria(AssertionConsumerService::class),
            new LocationCriteria($recipient),
        ]);

        $endpoints = (new DescriptorTypeEndpointResolver())
            ->resolve($criteriaSet, $this->getServiceProviderEntityDescriptor()->getAllEndpoints());

        if (empty($endpoints)) {
            throw new LightSamlValidationException("The recipient endpoint in the assertion did not match the service provider's configured endpoints");
        }
    }

    protected function validateRepeatedId(): void
    {
        $assertion = $this->getFirstAssertion();
        $key = collect([self::ID_CACHE_PREFIX, $assertion->getIssuer()->getValue(), $assertion->getId()])->join('-');

        if (Cache::has($key)) {
            throw new LightSamlValidationException('The identity provider repeated an assertion id');
        }

        Cache::put($key, true, $this->getConfig('validation.repeated_id_ttl'));
    }

    protected function validateTimestamps(): void
    {
        (new AssertionTimeValidator())
            ->validateTimeRestrictions($this->getFirstAssertion(), Carbon::now()->timestamp, $this->getConfig('validation.clock_skew', 120));
    }

    protected function validateSignature(): void
    {
        $keyDescriptors = $this->getIdentityProviderEntityDescriptor()
            ->getFirstIdpSsoDescriptor()
            ->getAllKeyDescriptorsByUse(KeyDescriptor::USE_SIGNING);

        /** @var SignatureXmlReader $signatureReader */
        $signatureReader = $this->messageContext->getMessage()->getSignature() ?: $this->getFirstAssertion()->getSignature();

        if (!$signatureReader) {
            throw new InvalidSignatureException('The received assertion had no available signature');
        }

        foreach ($keyDescriptors as $keyDescriptor) {
            $key = KeyHelper::createPublicKey($keyDescriptor->getCertificate());

            try {
                if ($signatureReader->validate($key)) {
                    return;
                }
            } catch (LightSamlSecurityException $e) {
                continue;
            }
        }

        throw new InvalidSignatureException('The signature of the assertion could not be verified');
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

        $this->validateAssertion();
        $this->validateIssuer();
        $this->validateRecipient();
        $this->validateRepeatedId();
        $this->validateTimestamps();
        $this->validateSignature();

        $assertion = $this->getFirstAssertion();
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
            throw new LightSamlValidationException('Server responded with an unsuccessful status: '.$status->getStatusCode()->getValue());
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
        Cache::forget(self::METADATA_CACHE_KEY);
        Cache::forget(self::METADATA_CACHE_KEY_TTL);
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
