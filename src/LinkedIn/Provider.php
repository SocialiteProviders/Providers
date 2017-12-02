<?php

namespace SocialiteProviders\LinkedIn;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'LINKEDIN';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['r_basicprofile', 'r_emailaddress'];

    /**
     * Get the GET parameters for the code request.
     *
     * @param string|null $state
     *
     * @return array
     */
    protected function getCodeFields($state = null)
    {
        $fields = [
            'client_id' => $this->clientId, 'redirect_uri' => $this->redirectUrl,
            'scope' => $this->formatScopes($this->scopes, $this->scopeSeparator),
            'response_type' => 'code',
        ];

        if ($this->usesState()) {
            $fields['state'] = $state;
        }

        return array_merge($fields, $this->parameters);
    }

    /**
     * Determine if the provider is operating with state.
     *
     * @return bool
     */
    protected function usesState()
    {
        // linkedin needs this
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://www.linkedin.com/uas/oauth2/authorization', $state
        );
    }

    /**
     * Redirect the user of the application to the provider's authentication screen.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect()
    {
        $state = Str::random(40);

        if (! $this->isStateless()) {
            $this->request->getSession()->put('state', $state);
        }

        return new RedirectResponse($this->getAuthUrl($state));
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://www.linkedin.com/uas/oauth2/accessToken';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.linkedin.com/v1/people/~:(id,formatted-name,picture-url,email-address,public-profile-url)', [
            'headers' => [
                'Accept-Language' => 'en-US',
                'x-li-format' => 'json',
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['id'], 'nickname' => null,
            'name' => $user['formattedName'], 'email' => $user['emailAddress'],
            'avatar' => Arr::get($user, 'pictureUrl'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'form_params' => $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode($response->getBody(), true);

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return [
            'client_id' => $this->clientId, 'client_secret' => $this->clientSecret,
            'code' => $code, 'redirect_uri' => $this->redirectUrl,
            'grant_type' => 'authorization_code',
        ];
    }
}
