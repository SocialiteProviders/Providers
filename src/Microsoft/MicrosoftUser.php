<?php

namespace SocialiteProviders\Microsoft;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\User;

class MicrosoftUser extends User
{
    /**
     * The HTTP client used to fetch the avatar.
     *
     * The provider hands over its own client, so the avatar request honours
     * the same Guzzle options (e.g. a timeout) as every other request.
     */
    protected ?Client $httpClient = null;

    /**
     * The proxy used to fetch the avatar, in the format of Guzzle's proxy option.
     *
     * @var array<string, mixed>|string|null
     */
    protected array|string|null $proxy = null;

    /**
     * Set the HTTP client used to fetch the avatar.
     *
     * @return $this
     */
    public function setHttpClient(Client $client): static
    {
        $this->httpClient = $client;

        return $this;
    }

    /**
     * Set the proxy used to fetch the avatar.
     *
     * @param  array<string, mixed>|string|null  $proxy
     * @return $this
     */
    public function setProxy(array|string|null $proxy): static
    {
        $this->proxy = $proxy;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvatar()
    {
        try {
            $response = $this->getHttpClient()->get(
                'https://graph.microsoft.com/v1.0/me/photo/$value',
                [
                    RequestOptions::HEADERS => [
                        'Accept'        => 'image/*',
                        'Authorization' => 'Bearer '.$this->token,
                    ],
                    RequestOptions::PROXY => $this->proxy,
                ]
            );

            return (new MicrosoftAvatar)->setResponse($response);
        } catch (TransferException) {
            return null;
        }
    }

    /**
     * Prepare the user for serialization.
     *
     * The HTTP client holds closures and cannot be serialized. It is left out,
     * an unserialized user falls back to a default client.
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        $data = get_object_vars($this);

        unset($data['httpClient']);

        return $data;
    }

    /**
     * Restore the user from its serialized data.
     *
     * @param  array<string, mixed>  $data
     */
    public function __unserialize(array $data): void
    {
        foreach ($data as $property => $value) {
            $this->{$property} = $value;
        }
    }

    protected function getHttpClient(): Client
    {
        return $this->httpClient ??= new Client;
    }
}
