# OpenID Connect

```bash
composer require socialiteproviders/openidconnect
```

A generic [OpenID Connect](https://openid.net/connect/) provider for Laravel Socialite. The provider discovers its endpoints via the issuer's `/.well-known/openid-configuration` document, supports JWT signature verification (JWKS or a configured public key), nonce validation, and PKCE.

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/),
then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'openidconnect' => [
    'base_url'       => env('OIDC_BASE_URL'),
    'client_id'      => env('OIDC_CLIENT_ID'),
    'client_secret'  => env('OIDC_CLIENT_SECRET'),
    'redirect'       => env('OIDC_REDIRECT_URI'),

    // Optional: space-separated list of extra scopes to request in addition
    // to the defaults ('openid email profile').
    'scopes'         => env('OIDC_SCOPES'),

    // Optional: when true, id_token signatures will be verified using the
    // provider's JWKS (or 'jwt_public_key' below if set).
    'verify_jwt'     => env('OIDC_VERIFY_JWT', false),

    // Optional: PEM-encoded public key used to verify id_token signatures
    // instead of fetching the JWKS.
    'jwt_public_key' => env('OIDC_JWT_PUBLIC_KEY'),

    // Optional: signing algorithm hint (e.g. RS256, RS512, ES256, PS256).
    // Defaults to the alg in the id_token header, or RS256.
    'jwt_algorithm'  => env('OIDC_JWT_ALGORITHM'),

    // Optional: override the expected `iss` claim. Defaults to the issuer
    // reported by the discovery document.
    'issuer'         => env('OIDC_ISSUER'),

    // Optional: client authentication method at the token endpoint.
    // Accepts 'client_secret_basic' or 'client_secret_post'. If omitted,
    // the provider picks whichever the IdP advertises (preferring basic).
    'token_auth_method'        => env('OIDC_TOKEN_AUTH_METHOD'),

    // Optional: default post-logout redirect URI used by the logout() helper.
    'post_logout_redirect_uri' => env('OIDC_POST_LOGOUT_REDIRECT_URI'),

    // Optional: TTL (in seconds) for the cached discovery document and JWKS.
    // Defaults to 3600 (1 hour).
    'cache_ttl'                => env('OIDC_CACHE_TTL'),

    // Optional: clock skew leeway (in seconds) applied to exp/nbf/iat. 0 by default.
    'clock_skew'               => env('OIDC_CLOCK_SKEW'),

    // Optional: Guzzle connect/read timeouts for IdP calls. Defaults: 5s/10s.
    'http_connect_timeout'     => env('OIDC_HTTP_CONNECT_TIMEOUT'),
    'http_timeout'             => env('OIDC_HTTP_TIMEOUT'),
],
```

The Authorization Code Flow with PKCE is used by default (`response_type=code`, PKCE is enabled on the `Provider`).

`base_url` is the OIDC issuer URL (for example `https://id.example.com`). The provider appends `/.well-known/openid-configuration` automatically.

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('openidconnect', \SocialiteProviders\OpenIDConnect\Provider::class);
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
        \SocialiteProviders\OpenIDConnect\OpenIDConnectExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

```php
return Socialite::driver('openidconnect')->redirect();
```

```php
$user = Socialite::driver('openidconnect')->user();
```

The returned user is populated from the `id_token` claims, falling back to the `userinfo` endpoint when the id_token does not contain an email. Mapped fields include `id` (`sub`), `email`, `name`, `nickname`, `given_name`, `family_name`, `idp`, `role`, and `groups`. The raw user also contains `id_token`, which you should stash in the session at login if you want to drive RP-initiated logout later.

### RP-Initiated Logout

If the IdP advertises an `end_session_endpoint` in its discovery document, you can build a logout redirect:

```php
// In your login callback, stash the id_token so you can pass it back at logout.
session(['oidc_id_token' => $user->user['id_token']]);

// In your logout controller:
return Socialite::driver('openidconnect')
    ->logout(session('oidc_id_token'), route('home'));
```

Most IdPs require `id_token_hint` (the id_token from login) and a `post_logout_redirect_uri` that has been pre-registered with the client.
