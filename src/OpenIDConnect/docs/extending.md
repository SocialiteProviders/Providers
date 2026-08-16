# Extending

There are two extension points: a provider class per connection, and an issuer validator for the one check whose shape varies between IdPs.

## Custom provider classes

The `provider` key in a connection names the class that drives it. Built-in shorthands (`entra`, `keycloak`, `auth0`, `okta`, `google`) map to subclasses shipped with the package. Any class extending `SocialiteProviders\OpenIDConnect\Provider` works the same way. There is no registration step: Socialite instantiates the class when the driver is first resolved.

A subclass usually overrides two things:

- `configDefaults(array $config): array`. Defaults derived from the connection's own values, merged under whatever the user set explicitly. This is where `base_url` gets built from a friendlier key, or a quirk gets pinned.
- `additionalConfigKeys(): array`. Any extra keys the class reads, so the manager's config retriever passes them through.

```php
use SocialiteProviders\OpenIDConnect\Provider;

class MyCorpProvider extends Provider
{
    public static function additionalConfigKeys(): array
    {
        return array_merge(parent::additionalConfigKeys(), ['tenant']);
    }

    protected function configDefaults(array $config): array
    {
        return [
            'base_url'   => 'https://sso.mycorp.example/tenants/'.($config['tenant'] ?? 'default'),
            'scopes'     => ['email', 'mycorp:roles'],
            'clock_skew' => 30,
        ];
    }
}
```

```php
// config/oidc.php
'connections' => [
    'mycorp' => [
        'provider'      => MyCorpProvider::class,
        'tenant'        => 'acme',
        'client_id'     => env('MYCORP_CLIENT_ID'),
        'client_secret' => env('MYCORP_CLIENT_SECRET'),
        'redirect'      => env('MYCORP_REDIRECT_URI'),
    ],
],
```

For deeper changes, override the relevant method. The common ones:

| Override | To change |
|---|---|
| `mapUserToObject(array $user)` | Which claims land on the Socialite user |
| `getScopes()` / `$scopes` | Scope handling |
| `getBaseUrl()` | How the issuer URL is resolved or constrained |
| `resolveTokenAuthMethod()` | Client authentication at the token endpoint |
| `validateIdTokenClaims($payload, $alg, $accessToken)` | Claim validation (call `parent::` and add, rather than replace) |
| `getHttpClient()` | HTTP behaviour (middleware, mTLS; a plain proxy is the `proxy` config key) |

The built-in provider classes in [`src/Providers/`](../src/Providers) are the reference examples. Each is a few lines.

## Issuer validators

The `iss` claim is checked on every id_token and every back-channel logout token. The expected value is the `issuer` config, falling back to the discovery document's, and the default comparison is strict equality.

Some IdPs don't emit their issuer verbatim, so the comparison is pluggable. A validator implements one method and gets the full token payload, so other claims can inform the decision:

```php
use SocialiteProviders\OpenIDConnect\IssuerValidators\IssuerValidator;
use stdClass;

class MyIssuerValidator implements IssuerValidator
{
    public function validate(string $expectedIssuer, stdClass $payload): bool
    {
        return str_starts_with($payload->iss ?? '', 'https://sso.mycorp.example/');
    }
}
```

Wire it in at whichever level fits:

```php
// Per connection, resolved through the container:
'issuer_validator' => MyIssuerValidator::class,

// Per request, a class instance or a closure:
Socialite::driver('oidc_mycorp')
    ->validateIssuerUsing(fn (string $expected, stdClass $payload) => /* bool */)
    ->user();

// Per provider class, as a derived default, the way EntraProvider does:
protected function configDefaults(array $config): array
{
    return ['issuer_validator' => MyIssuerValidator::class];
}
```

### The Entra issuer template

Entra ID advertises the issuer for multi-tenant apps as a literal template, placeholder unsubstituted:

```
https://login.microsoftonline.com/{tenantid}/v2.0
```

A strict comparison can never match that. `EntraProvider` defaults `issuer_validator` to `EntraIssuerValidator`, which substitutes the token's `tid` claim into the placeholder before comparing exactly, so `'provider' => 'entra'` needs no issuer configuration at all. Single-tenant setups have no placeholder and are compared strictly, unchanged.

### Entra and email

Entra's `email` claim is the directory contact email, often empty and settable to any address by any tenant admin with no domain verification. Treating it as an account identifier in a multi-tenant app is the [nOAuth account takeover](https://www.descope.com/blog/post/noauth). The login identity lives in `preferred_username` instead: for work and school accounts it's the UPN, whose domain the issuing tenant has to have verified.

So `EntraProvider` resolves the user's email from `preferred_username` only, skipping values that aren't email-shaped (`preferred_username` can be a phone number). It deliberately does not fall back to `email`. A fallback would surface the spoofable value in exactly the case that matters: a hostile tenant minting a token with a phone-shaped `preferred_username` and any `email` it likes. If the fallback is fine for you (a single-tenant app trusting its own directory, say), opt in per connection:

```php
'email_claims' => ['preferred_username', 'email'],
```

Whatever claim it comes from, treat the email as display data. Key accounts on `$user->getId()` (the `sub` claim), which is immutable and can't be spoofed across tenants. If you must link accounts by email, request Microsoft's `xms_edov` optional claim and only trust the email when it says the domain owner is verified. Querying Microsoft Graph doesn't help: it returns the same directory attributes, controlled by the same tenant admin.
