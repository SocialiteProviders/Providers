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
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use LightSaml\Binding\BindingFactory;
use LightSaml\Binding\HttpRedirectBinding;
use LightSaml\Builder\EntityDescriptor\SimpleEntityDescriptorBuilder;
use LightSaml\ClaimTypes;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Credential\KeyHelper;
use LightSaml\Credential\X509Certificate;
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
use LightSaml\Model\XmlDSig\SignatureXmlReader;
use LightSaml\SamlConstants;
use SocialiteProviders\Manager\Contracts\ConfigInterface;
use SocialiteProviders\Manager\Exception\MissingConfigException;

class Provider extends AbstractProvider implements SocialiteProvider
{
    /**
     * The HTTP request instance.
     *
     * @var Request
     */
    protected $request;

    /**
     * The SAML2 User instance.
     *
     * @var User
     */
    protected $user;

    /**
     * The configuration
     *
     * @var array
     */
    protected $config;

    public function __construct(Request $request)
    {
        parent::__construct($request, '', '', '');
    }

    public function setConfig(ConfigInterface $config): Provider
    {
        $config = $config->get();

        $this->config = $config;

        return $this;
    }

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

        $identityProviderConsumerService = $this->getIdentityProviderEntityDescriptor()
            ->getFirstIdpSsoDescriptor()
            ->getFirstSingleSignOnService(SamlConstants::BINDING_SAML2_HTTP_REDIRECT);

        $authnRequest = new AuthnRequest();
        $authnRequest
            ->setID(Helper::generateID())
            ->setProtocolBinding($this->getAssertionConsumerServiceBinding())
            ->setRelayState($state)
            ->setIssueInstant(new DateTime())
            ->setDestination($identityProviderConsumerService->getLocation())
            ->setIssuer(new Issuer($this->getServiceProviderEntityDescriptor()->getEntityID()));

        $redirectBinding = new HttpRedirectBinding();

        $messageContext = new MessageContext();
        $messageContext->setMessage($authnRequest);

        return $redirectBinding->send($messageContext);
    }

    protected function getIdentityProviderEntityDescriptorManually(): EntityDescriptor
    {
        $acs = $this->getConfig('acs');
        $entityId = $this->getConfig('entityid');
        $certificate = $this->getConfig('certificate');

        if (!$entityId || !$certificate) {
            throw new MissingConfigException('When using "acs", both "entityid" and "certificate" must be set');
        }

        $x509 = new X509Certificate();
        $x509->loadPem($certificate);

        $builder = new SimpleEntityDescriptorBuilder($entityId, $acs, '', $x509);

        return $builder->get();
    }

    protected function getIdentityProviderEntityDescriptorFromXml(): EntityDescriptor
    {
        return Metadata::fromXML($this->getConfig('metadata'), new DeserializationContext());
    }

    protected function getIdentityProviderEntityDescriptorFromUrl(): EntityDescriptor
    {
        $key = 'socialite.saml2.metadata';
        $fetchTimeKey = $key.'.ttl';

        $metadataUrl = $this->getConfig('metadata');
        $xml = Cache::get($key);
        $ttl = Cache::get($fetchTimeKey);

        $deserializationContext = new DeserializationContext();

        if ($xml && $ttl && $ttl + $this->getConfig('ttl', 86400) < time()) {
            return Metadata::fromXML($xml, $deserializationContext);
        }

        Cache::forever($fetchTimeKey, time());

        try {
            $xml = $this->getHttpClient()
                ->get($metadataUrl)
                ->getBody()
                ->getContents();

            Cache::forever($key, $xml);
        } catch (GuzzleException $e) {
            if (!$xml) {
                throw $e;
            }
        }

        return Metadata::fromXML($xml, $deserializationContext);
    }

    /**
     * @return EntityDescriptor
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
            ->setEntityID(URL::to('auth/saml2'))
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

    public function getServiceProviderAssertionConsumerUrl()
    {
        return $this->getServiceProviderEntityDescriptor()
            ->getFirstSpSsoDescriptor()
            ->getFirstAssertionConsumerService()
            ->getLocation();
    }

    public function getServiceProviderEntityId()
    {
        return $this->getServiceProviderEntityDescriptor()
            ->getEntityID();
    }

    protected function getAssertionConsumerServiceRoute(): string
    {
        return $this->getConfig('sp_acs', 'auth/callback');
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

    public function user()
    {
        if ($this->user) {
            return $this->user;
        }

        if ($this->hasInvalidState()) {
            throw new InvalidStateException;
        }

        if ($this->hasInvalidSignature()) {
            throw new InvalidSignatureException;
        }

        $bindingFactory = new BindingFactory();
        $binding = $bindingFactory->getBindingByRequest($this->request);

        $messageContext = new MessageContext();
        $binding->receive($this->request, $messageContext);

        $assertion = $messageContext->asResponse()->getFirstAssertion();
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
        $state = $this->request->session()->pull('state');

        $bindingFactory = new BindingFactory();
        $binding = $bindingFactory->getBindingByRequest($this->request);

        $messageContext = new MessageContext();
        $binding->receive($this->request, $messageContext);

        return $state !== $messageContext->getMessage()->getRelayState();
    }

    protected function hasInvalidSignature(): bool
    {
        $bindingFactory = new BindingFactory();
        $binding = $bindingFactory->getBindingByRequest($this->request);

        $messageContext = new MessageContext();
        $binding->receive($this->request, $messageContext);

        $key = KeyHelper::createPublicKey(
            $this->getIdentityProviderEntityDescriptor()
                ->getFirstIdpSsoDescriptor()
                ->getFirstKeyDescriptor(KeyDescriptor::USE_SIGNING)
                ->getCertificate()
        );

        /** @var SignatureXmlReader $signatureReader */
        $signatureReader = $this->getAssertionConsumerServiceBinding() === SamlConstants::BINDING_SAML2_HTTP_REDIRECT ?
            $messageContext->getMessage()->getSignature() :
            $messageContext->asResponse()->getFirstAssertion()->getSignature();

        return !$signatureReader->validate($key);
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

    protected function getTokenUrl()
    {
    }

    protected function getAuthUrl($state)
    {
    }

    protected function getUserByToken($token)
    {
    }

    protected function mapUserToObject(array $user)
    {
    }
}
