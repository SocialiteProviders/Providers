<?php

namespace SocialiteProviders\Microsoft;

use GuzzleHttp\Client;
use SocialiteProviders\Manager\OAuth2\User;

class MicrosoftUser extends User
{
    /**
     * {@inheritdoc}
     */
    public function getAvatar()
    {
        $client = new Client();
        $response = $client->get(
            'https://graph.microsoft.com/v1.0/me/photo/$value',
            [
                'headers' => [
                    'Accept'        => 'image/*',
                    'Authorization' => 'Bearer '.$this->token,
                ],
            ]
        );

        return (new MicrosoftAvatar())->setResponse($response);
    }
}
