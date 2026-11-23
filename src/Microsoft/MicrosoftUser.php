<?php

namespace SocialiteProviders\Microsoft;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @deprecated Will be removed in 5.0. The provider will return SocialiteProviders\Manager\OAuth2\User instead.
 */
class MicrosoftUser extends User
{
    /**
     * {@inheritdoc}
     *
     * In 5.0 this method will no longer send a request. It will return the photo as a
     * data URL if the include_avatar option is enabled, and null otherwise.
     */
    public function getAvatar()
    {
        $client = new Client;

        try {
            $response = $client->get(
                'https://graph.microsoft.com/v1.0/me/photo/$value',
                [
                    RequestOptions::HEADERS => [
                        'Accept'        => 'image/*',
                        'Authorization' => 'Bearer '.$this->token,
                    ],
                ]
            );

            return (new MicrosoftAvatar)->setResponse($response);
        } catch (ClientException) {
            return null;
        }
    }
}
