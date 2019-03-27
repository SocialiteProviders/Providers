<?php

namespace SocialiteProviders\LinkedIn;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'LINKEDIN';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['r_liteprofile', 'r_emailaddress'];

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
            'client_id'     => $this->clientId, 'redirect_uri' => $this->redirectUrl,
            'scope'         => $this->formatScopes($this->scopes, $this->scopeSeparator),
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
            'https://www.linkedin.com/oauth/v2/authorization', $state
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

        if (!$this->isStateless()) {
            $this->request->getSession()->put('state', $state);
        }

        return new RedirectResponse($this->getAuthUrl($state));
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://www.linkedin.com/oauth/v2/accessToken';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $requestHeaders = [
                'Accept-Language' => 'en-US',
                'x-li-format' => 'json',
                'Authorization' => 'Bearer '.$token,
            ];

        $meResponse = $this->getHttpClient()->get(
            'https://api.linkedin.com/v2/me?projection=(id,lastName,firstName,vanityName,profilePicture(displayImage~:playableStreams))', [
            'headers' => $requestHeaders,
        ]);

        $meResponseBody = json_decode($meResponse->getBody()->getContents(), true);

        $avatar = null;

        if (array_key_exists('profilePicture', $meResponseBody)) {
            $avatars = new Collection($meResponseBody['profilePicture']['displayImage~']['elements']);

            if ($avatars->count() > 0) {
                $avatar = $avatars->pop()['identifiers'][0]['identifier'];
            }
        }

        $emailResponse = $this->getHttpClient()->get(
            'https://api.linkedin.com/v2/clientAwareMemberHandles?q=members&projection=(elements*(primary,type,handle~))', [
            'headers' => $requestHeaders,
        ]);

        $emailResponseBody = json_decode($emailResponse->getBody()->getContents(), true);

        return [
            'nickname'  => null,
            'id'        => $meResponseBody['id'],
            'name'      => reset($meResponseBody['firstName']['localized']) . ' ' . reset($meResponseBody['lastName']['localized']),
            'avatar'    => $avatar,
            'email'     => $emailResponseBody['elements'][0]['handle~']['emailAddress'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map($user);
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
            'client_id'  => $this->clientId, 'client_secret' => $this->clientSecret,
            'code'       => $code, 'redirect_uri' => $this->redirectUrl,
            'grant_type' => 'authorization_code',
        ];
    }
}
