<?php

namespace SocialiteProviders\Flickr;

use SocialiteProviders\Manager\OAuth1\User;
use League\OAuth1\Client\Credentials\TokenCredentials;
use SocialiteProviders\Manager\OAuth1\Server as BaseServer;

class Server extends BaseServer
{
    /**
     * {@inheritdoc}
     */
    public function urlTemporaryCredentials()
    {
        return 'https://www.flickr.com/services/oauth/request_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlAuthorization()
    {
        return 'https://www.flickr.com/services/oauth/authorize';
    }

    /**
     * {@inheritdoc}
     */
    public function urlTokenCredentials()
    {
        return 'https://www.flickr.com/services/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function urlUserDetails()
    {
        return 'https://api.flickr.com/services/rest/?method=flickr.test.login&format=json&nojsoncallback=1';
    }

    /**
     * {@inheritdoc}
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        $data = $this->getProfile($data['user']['id']);
        $data = $data['person'];

        $user = new User();
        $user->id = $data['id'];
        $user->nickname = $data['username']['_content'];
        $user->name = $data['realname']['_content'];
        $user->extra = array_diff_key($data, array_flip([
            'id', 'username', 'realname',
        ]));

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        return $data['users'][0]['id'];
    }

    /**
     * {@inheritdoc}
     */
    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
        return $data['users'][0]['email'];
    }

    /**
     * {@inheritdoc}
     */
    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        return $data['users'][0]['username'];
    }

    /**
     * Get detals about the current user.
     *
     * @param string $userId
     *
     * @return array
     */
    public function getProfile($userId)
    {
        $parameters = [
            'method' => 'flickr.people.getInfo',
            'format' => 'json',
            'nojsoncallback' => 1,
            'user_id' => $userId,
            'api_key' => $this->clientCredentials->getIdentifier(),
        ];

        $url = 'https://api.flickr.com/services/rest/?'.http_build_query($parameters);

        $client = $this->createHttpClient();

        $response = $client->get($url)->send();

        return $response->json();
    }
}
