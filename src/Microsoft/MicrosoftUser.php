<?php

namespace SocialiteProviders\Microsoft;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\User;

class MicrosoftUser extends User
{
    /**
     * {@inheritdoc}
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
