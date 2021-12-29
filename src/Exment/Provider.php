<?php

namespace SocialiteProviders\Exment;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'EXMENT';

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['me'];
	
    public static function additionalConfigKeys()
    {
        return ['exment_uri'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            $this->urlJoin($this->getBaseUri(), 'oauth/authorize'),
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->urlJoin($this->getBaseUri(), 'oauth/token');
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            $this->urlJoin($this->getBaseUri(), 'api/me'),
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'   => $user['id'], 'nickname' => $user['value']['user_code'],
            'name' => $user['value']['user_name'], 'email' => $user['value']['email'], 'avatar' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }



    // Custom functions ----------------------------------------------
    /**
     * Get Exment base uri.
     * 
     * @return string
     */
    protected function getBaseUri(): string
    {
        $exment_url = $this->getConfig('exment_uri');
        if(is_null($exment_url)){
            throw new NotConfigExmentUrlException;
        }

        return $exment_url;
    }
    
    /**
     * Join UrlPath.
     */
    protected function urlJoin(...$pass_array)
    {
        return $this->joinPaths("/", $pass_array);
    }


    /**
     * Join path using trim_str.
     */
    protected function joinPaths($trim_str, $pass_array)
    {
        $ret_pass   =   "";

        foreach ($pass_array as $value) {
            if (empty($value)) {
                continue;
            }
            
            if (is_array($value)) {
                $ret_pass = $ret_pass.$trim_str.$this->joinPaths($trim_str, $value);
            } elseif ($ret_pass == "") {
                $ret_pass   =   $value;
            } else {
                $ret_pass   =   rtrim($ret_pass, $trim_str);
                $value      =   ltrim($value, $trim_str);
                $ret_pass   =   $ret_pass.$trim_str.$value;
            }
        }
        return $ret_pass;
    }

}
