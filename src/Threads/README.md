# Threads

```bash
composer require socialiteproviders/threads
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'threads' => [
  'client_id' => env('THREADS_CLIENT_ID'),
  'client_secret' => env('THREADS_CLIENT_SECRET'),
  'redirect' => env('THREADS_REDIRECT_URI')
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

-   Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('threads', \SocialiteProviders\Threads\Provider::class);
});
```

<details>
<summary>
Laravel 10 or below
</summary>
Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Threads\ThreadsExtendSocialite::class.'@handle',
    ],
];
```

</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('threads')->redirect();
```

### Returned User fields

-   `id`
-   `nickname`
-   `avatar`

### Refreshing access tokens

Threads does not support refresh tokens. It is however possible to exchange the default short-lived access tokens for long-lived refreshable access tokens. Socialite only supports the conventional way of refreshing access token with refresh tokens, so you need to implement this in your own code if you need to refresh access tokens.

First, exchange a short-lived access token for a long-lived access token.

```php
public function exchangeAccessToken(string $accessToken): string
{
    $response = Http::post('https://graph.threads.net/access_token', [
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json'
            ],
            RequestOptions::FORM_PARAMS => [
                'access_token'  => $accessToken,
                'client_secret' => config('threads.client_secret'),
                'grant_type'    => 'th_exchange_token',
            ],
        ]);

    $response = json_decode((string) $response->getBody(), true);

    return $response['access_token'];
}
```

After obtaining a long-lived access token, this token can be refreshed as long as it's still valid.

```php
public function refreshAccessToken(string $accessToken): string
{
    $response = Http::post('https://graph.threads.net/refresh_access_token', [
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json'
            ],
            RequestOptions::FORM_PARAMS => [
                'access_token'  => $accessToken,
                'grant_type'    => 'th_refresh_token',
            ],
        ]);

    $response = json_decode((string) $response->getBody(), true);

    return $response['access_token'];
}
```

### Reference

-   [Threads API](https://developers.facebook.com/docs/threads)
