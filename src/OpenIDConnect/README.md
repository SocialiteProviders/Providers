# OpenID Connect

A generic OpenID Connect driver for Laravel Socialite. Point it at any issuer that serves a discovery document: Keycloak, Entra ID, Auth0, Okta, Google, Authentik, or anything else that speaks OIDC. You can configure several issuers at once, and each becomes its own Socialite driver.

Endpoints come from the issuer's discovery document. Signing keys come from its JWKS and refresh automatically when the issuer rotates them. Every id_token is validated properly (signature, `iss`, `aud`, `azp`, `exp`, `nonce`, `at_hash`) and PKCE is on by default.

```bash
composer require socialiteproviders/openidconnect
```

## Configure

Publish the config file:

```bash
php artisan vendor:publish --tag=oidc-config
```

Add one entry per issuer to `config/oidc.php`:

```php
return [
    'connections' => [

        'keycloak' => [
            'provider'      => 'keycloak',
            'server_url'    => env('KEYCLOAK_SERVER_URL'),   // https://id.example.com
            'realm'         => env('KEYCLOAK_REALM'),
            'client_id'     => env('KEYCLOAK_CLIENT_ID'),
            'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
            'redirect'      => env('KEYCLOAK_REDIRECT_URI'),
        ],

        'entra' => [
            'provider'      => 'entra',
            'tenant'        => env('ENTRA_TENANT', 'common'),
            'client_id'     => env('ENTRA_CLIENT_ID'),
            'client_secret' => env('ENTRA_CLIENT_SECRET'),
            'redirect'      => env('ENTRA_REDIRECT_URI'),
        ],

        // Any OIDC-compliant issuer works with just its base URL:
        'authentik' => [
            'base_url'      => env('AUTHENTIK_BASE_URL'),
            'client_id'     => env('AUTHENTIK_CLIENT_ID'),
            'client_secret' => env('AUTHENTIK_CLIENT_SECRET'),
            'redirect'      => env('AUTHENTIK_REDIRECT_URI'),
        ],

    ],
];
```

Each connection becomes a Socialite driver named `oidc_{connection}`. Connections share nothing: each has its own credentials, endpoints and caches.

## Use

Vanilla Socialite from here:

```php
// Send the user to the IdP:
return Socialite::driver('oidc_keycloak')->redirect();

// In your callback controller:
$user = Socialite::driver('oidc_keycloak')->user();

$user->getId();       // sub, the one claim every IdP must return
$user->getEmail();    // null when the IdP didn't grant an email
$user->getName();
$user->getRaw();      // every claim from the id_token (merged with userinfo)
```

<details>
<summary>Everything available on the returned user</summary>

```php
$user->getId();                              // sub
$user->getRaw();                             // all claims
$user->accessTokenResponseBody['id_token'];  // the raw id_token, stash it for logout
$user->approvedScopes;                       // scopes the IdP actually granted
$user->token;                                // access token
$user->refreshToken;                         // refresh token, if granted
```

Mapped fields: `id` (`sub`), `email`, `name`, `nickname`, `given_name`, `family_name`, `idp`, `role`, `groups`.

`email` isn't guaranteed. It depends on the `email` scope being granted, so a user without one gets a null email rather than a failed login. Set `require_email` if your application can't proceed without it. When the id_token has no email, the userinfo endpoint is consulted automatically and its claims merged in.

Access and refresh tokens are bearer credentials. If you persist them, encrypt them at rest (Laravel's `encrypted` cast) and keep them out of JavaScript-readable storage.

</details>

For what comes after login (RP-initiated logout with state validation, RFC 7009 token revocation, back-channel logout, refreshing tokens, storing tokens safely) see [docs/logout.md](docs/logout.md).

## Built-in providers

The `provider` key picks the class that drives a connection. The built-in classes know the URL shape for their IdP, so you set `tenant` or `realm` or `domain` instead of building the base URL yourself. Anything you set explicitly wins over what the class derives:

| `provider` | Reads | Derives |
|---|---|---|
| `entra` | `tenant` (default `common`) | `base_url`, multi-tenant issuer handling ([details](docs/extending.md#the-entra-issuer-template)), email from `preferred_username` ([why](docs/extending.md#entra-and-email)) |
| `keycloak` | `server_url`, `realm` | `base_url` = `{server_url}/realms/{realm}` |
| `auth0` | `domain` | `base_url`, `client_secret_post` token auth |
| `okta` | `domain`, `auth_server` | `base_url`, `/oauth2/{auth_server}` when named |
| `google` | none | `base_url` = `https://accounts.google.com` |

For any other issuer, omit `provider` and set `base_url` directly. To encode your own IdP's shape, write a provider class and put its class name in `provider`. [docs/extending.md](docs/extending.md) covers the `configDefaults()` hook, the commonly overridden methods, and issuer validation (including [how Entra multi-tenant is handled](docs/extending.md#the-entra-issuer-template)).

## Configuration reference

<details>
<summary>All connection keys</summary>

Required: `client_id`, `client_secret`, `redirect`, and `base_url` (unless a built-in provider derives it).

| Key | Default | Meaning |
|---|---|---|
| `base_url` | none | Issuer URL; discovery is `{base_url}/.well-known/openid-configuration`. https required (loopback hosts exempt, so local dev works). |
| `provider` | `Provider::class` | Built-in shorthand or a Provider subclass name. |
| `scopes` | `openid email profile` | Replaces the defaults. Array, or string separated by whitespace/commas. `openid` is always sent. |
| `email_claims` | `['email']` | Claims consulted for the user's email, first non-empty wins. The `entra` provider defaults this to `['preferred_username']` ([why](docs/extending.md#entra-and-email)). |
| `verify_jwt` | `true` | Verify id_token signatures. Only disable for an OP that can't serve a JWKS; back-channel logout tokens are always verified regardless. |
| `jwt_public_key` | none | PEM public key used instead of fetching the JWKS. |
| `jwt_algorithm` | advertised algs, else `RS256` | Pin the accepted signing algorithm(s), e.g. `RS256` or `RS256,ES256`. |
| `issuer` | discovery `issuer` | Override the expected `iss` claim. |
| `issuer_validator` | strict equality | [`IssuerValidator`](docs/extending.md#issuer-validators) class, for issuers an exact comparison can't handle. |
| `token_auth_method` | advertised, preferring basic | `client_secret_basic` or `client_secret_post`. |
| `use_nonce` | `true` | Send and validate a nonce. Ignored (always off) in stateless mode. |
| `require_email` | `false` | Fail the login when no email can be obtained. |
| `post_logout_redirect_uri` | none | Default for the [`logout()`](docs/logout.md) helper. |
| `logout_token_replay_ttl` | token `exp` + skew | Seconds a back-channel logout `jti` is remembered. `0` disables built-in replay protection. |
| `cache_ttl` | `3600` | TTL for the cached discovery document and JWKS. |
| `clock_skew` | `0` | Leeway in seconds applied to `exp`/`nbf`/`iat`. |
| `http_timeout` / `http_connect_timeout` | `10` / `5` | Guzzle timeouts for IdP calls. |
| `proxy` | none | Proxy for IdP calls, in [Guzzle's format](https://docs.guzzlephp.org/en/stable/request-options.html#proxy) (a URL string, or an array per scheme). |

The `driver_prefix` config key (default `oidc_`) controls the driver names.

</details>

<details>
<summary>Single issuer without a config file</summary>

A plain `services.php` entry registers an `openidconnect` driver with no `config/oidc.php` at all. The same keys apply, including `provider`:

```php
'openidconnect' => [
    'base_url'      => env('OIDC_BASE_URL'),
    'client_id'     => env('OIDC_CLIENT_ID'),
    'client_secret' => env('OIDC_CLIENT_SECRET'),
    'redirect'      => env('OIDC_REDIRECT_URI'),
],
```

</details>

## Security

Every login validates the state (CSRF), the id_token signature against the discovered JWKS (refetched when keys rotate), the signing algorithm against an allow list, `iss`, `aud`/`azp`, `exp`/`nbf`/`iat`, the `nonce` (cleared after use, so replays fail), `at_hash` against the access token, and `sub`. PKCE is on by default. [docs/security.md](docs/security.md) has the full detail.

<details>
<summary>Stateless mode and PKCE</summary>

The nonce and the PKCE verifier both live in the session between the redirect and the callback:

- `->stateless()` skips the nonce automatically. That's safe for the code flow, where the code is bound to the client by PKCE and exchanged over the back channel. PKCE still works as long as a session store is bound to the request.
- With genuinely no session, add `->withoutPKCE()`:

```php
Socialite::driver('oidc_keycloak')->stateless()->withoutPKCE()->redirect();
```

`withoutNonce()` disables the nonce alone.

</details>

## Testing

```bash
composer test
```

The suite covers discovery and caching, JWKS key rotation, algorithm and claim validation, nonce replay, userinfo merging, token endpoint auth methods, the logout flows, the built-in provider classes, and multi-connection isolation.

## Credits

The provider implementation originates from [SocialiteProviders/Providers#1447](https://github.com/SocialiteProviders/Providers/pull/1447) by [adrum](https://github.com/adrum).

## License

MIT
