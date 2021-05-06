<?php

namespace SocialiteProviders\SAML;

use Aacotroneo\Saml2\Saml2Auth;
use Aacotroneo\Saml2\Saml2User;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;
use SocialiteProviders\SAML\SAMLController;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'SAML';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [''];
    
    public $user;
    
    public function __construct()
    {
        config(['saml2_settings' => require_once('config/saml2_settings.php')]);
        config(['saml2.default_idp_settings' => require_once('config/default_idp_settings.php')]);
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return [
            'client_id',
            'client_secret',
            'redirect',
            'endpoint',
            'identifier',
            'cert',
            'entity',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject($user)
    {
        $defaults = ['id', 'nickname', 'name', 'email', 'avatar'];
        
        $raw = [];
        $map = [];
        
        $map['id'] = $user->getUserId();
        
        foreach (config('services.saml.variables') as $property => $samlAttribute) {
            if ($samlAttribute) {
                if ($value = $user->getAttribute($samlAttribute)) {
                    if (is_array($value)) $value = reset($value);
                    $raw[$property] = $value;
                    if (in_array($property, $defaults)) {
                        $map[$property] = $value;
                    }
                }
            }
        }
        
        if (!isset($map['name'])) {
            $name = [];
            
            if (isset($raw['firstname'])) {
                $name[] = $raw['firstname'];
            }
            
            if (isset($raw['lastname'])) {
                $name[] = $raw['lastname'];
            }
            
            if (count($name)) {
                $map['name'] = implode(' ', $name);
            }
        }
        
        $raw['raw'] = $user->getAttributes();
        
        return (new User())->setRaw($raw)->map($map);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code'
        ]);
    }
    
    private function getSaml2Auth()
    {
        $auth = Saml2Auth::loadOneLoginAuthFromIpdConfig('default');
        return new Saml2Auth($auth);
    }
    
    private function getController()
    {
        return new SAMLController;
    }
    
    public function redirect()
    {
        return $this->getController()->login($this->getSaml2Auth());
    }
    
    public function metadata()
    {
        return $this->getController()->metadata($this->getSaml2Auth());
    }
    
    public function user()
    {
        $saml2Auth = $this->getSaml2Auth();
        $errors = $saml2Auth->acs();
        
        if (!empty($errors)) {
            $message = 'SAML Error';
            
            if (isset($errors['last_error_reason'])) {
                $message .= ': ' . $errors['last_error_reason'];
            }
            
            throw new InvalidStateException($message);
        }
        
        $user = $this->mapUserToObject($saml2Auth->getSaml2User());
        
        return $user;
    }
}
