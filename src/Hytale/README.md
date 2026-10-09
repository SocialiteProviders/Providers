---
category: Gaming
---

# Hytale

```bash
composer require socialiteproviders/hytale
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

"Sign in with Hytale" is OpenID Connect on the dedicated issuer `https://connect.accounts.hytale.com`, restricted to the authorization code flow with PKCE.

There is no self-service client registration: apply for a client through the [developer portal](https://accounts.hytale.com/third-party-developer-apps), which requires signing in with a Hytale account, and staff review the request. The OAuth documentation lives in the same space. On approval you receive a `client_id`, and a `client_secret` for confidential clients (server-side apps such as a Laravel application).

### Add configuration to `config/services.php`

```php
'hytale' => [
  'client_id' => env('HYTALE_CLIENT_ID'),
  'client_secret' => env('HYTALE_CLIENT_SECRET'),
  'redirect' => env('HYTALE_REDIRECT_URI'),
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('hytale', \SocialiteProviders\Hytale\Provider::class);
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
        \SocialiteProviders\Hytale\HytaleExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('hytale')->redirect();
```

### Scopes

Scopes are matched exactly against what was granted to your client: requesting one your client was not approved for makes the whole request fail with `invalid_scope`. Only `openid` — the one scope every client needs — is requested by default.

The profile and optional account claims are opt-in per request, and each maps to `null` when its scope was not granted:

| Scope | Claims |
|---|---|
| `hytale:profile` | prompts the user to pick a game profile at sign-in and returns `profile` (`uuid`, `username`) |
| `account:shared_source` | `shared_source` (boolean) |
| `account:game_ownership` | `game_ownership` (boolean) |
| `account:parental_managed` | `parental_managed` (boolean) |

```php
return Socialite::driver('hytale')
    ->scopes(['hytale:profile', 'account:shared_source'])
    ->redirect();
```

Hytale shows the consent screen on every sign-in: there is no silent re-authentication, so `prompt=none` always fails.

### Callback errors

Authorization failures come back to your `redirect` URI as `error` and `error_description` query parameters (for example `access_denied` when the user declines consent), so check for `error` in your callback before calling `user()`:

```php
if ($request->has('error')) {
    return redirect('/')->with('error', $request->query('error_description'));
}

$user = Socialite::driver('hytale')->user();
```

### Refresh tokens

Refresh tokens are opt-in: enabled for your client in the portal, then requested with the `offline` scope. The provider does not use them — Socialite's `user()` is a login flow, not an API session. If you need to re-check `shared_source` later, send the user through the flow again.

### Security notes

Claims are read from `/userinfo`, called server-side over TLS with the access token, so the `id_token` that Hytale also returns is never validated or relied upon. Do not decode `$user->accessTokenResponseBody['id_token']` and trust its claims without validating it yourself (signature against the JWKS, `iss`, `aud`, `exp`). Key your users on `$user->getId()` — the anonymous per-application `sub` — never on profile values, which users can change by picking a different game profile at sign-in.

### Returned User fields

- `id` — the `sub` claim, a stable **per-application anonymous identifier**: the same player gets a different `sub` in every application
- `nickname` — the selected game profile's `username`, `null` unless `hytale:profile` was requested and granted
- `uuid` — the selected game profile's `uuid`, `null` unless `hytale:profile` was requested and granted
- `shared_source`, `game_ownership`, `parental_managed` — the optional account claims, `null` unless their scope was requested and granted

Hytale never returns a name, email or avatar, so those fields are always `null`. Key your users on `id`, not on the profile: the profile values (`uuid`, `username`) are public, shared game identities, while `sub` is unique to your application. The full profile payload also stays available through `$user->getRaw()['profile']`.
