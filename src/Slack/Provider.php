<?php

namespace SocialiteProviders\Slack;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'SLACK';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['identity.basic', 'identity.email', 'identity.team', 'identity.avatar'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ',';

    /**
     * Middleware that throws exceptions for non successful slack api calls
     * "http_error" request option is set to true.
     *
     * @return callable Returns a function that accepts the next handler.
     */
    private function getSlackApiErrorMiddleware()
    {
        return function (callable $handler) {
            return function ($request, array $options) use ($handler) {
                if (empty($options['http_errors'])) {
                    return $handler($request, $options);
                }
                return $handler($request, $options)->then(
                    function (ResponseInterface $response) use ($request, $handler) {
                        $body = json_decode($response->getBody()->getContents(), true);
                        $response->getBody()->rewind();

                        if ($body['ok']) {
                            return $response;
                        }

                        throw RequestException::create($request, $response);
                    }
                );
            };
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function getHttpClient()
    {
        $handler = HandlerStack::create();
        $handler->push($this->getSlackApiErrorMiddleware(), 'slack_api_errors');

        if (is_null($this->httpClient)) {
            $this->httpClient = new Client(['handler' => $handler]);
        }

        return $this->httpClient;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://slack.com/oauth/authorize', $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://slack.com/api/oauth.access';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://slack.com/api/users.identity?token='.$token
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['user']['id'],
            'name' => $user['user']['name'],
            'email' => $user['user']['email'],
            'avatar' => $user['user']['image_192'],
            'organization_id' => $user['team']['id'],
        ]);
    }
}
