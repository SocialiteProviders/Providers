---
category: Business
name: Frappe / ERPNext
---

# Frappe / ERPNext

Use any Frappe-based site, including ERPNext, Frappe HR, LMS, Helpdesk, Books,
and custom apps, as an OAuth2 / OpenID Connect provider.

```bash
composer require socialiteproviders/frappe
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/),
then follow the provider-specific instructions below.

### Create an OAuth Client in Frappe / ERPNext

See Frappe's official [How to setup OAuth Client](https://docs.frappe.io/framework/user/en/guides/integration/how_to_set_up_oauth#add-a-client-app)
guide, then set the redirect URI to your callback (e.g.
`https://your-app.test/auth/frappe/callback`) and the scopes to `openid`.
Use `openid all` when requesting additional User fields or REST API access.
After saving, use the generated App Client ID and App Client Secret below.

### Add configuration to `config/services.php`

```php
'frappe' => [
  'client_id' => env('FRAPPE_CLIENT_ID'),
  'client_secret' => env('FRAPPE_CLIENT_SECRET'),
  'redirect' => env('FRAPPE_REDIRECT_URI'),
  'base_url' => env('FRAPPE_BASE_URL'), // Also used for logout and token revocation
],
```

Set the site root, without an API path, in `.env`:

```dotenv
FRAPPE_CLIENT_ID=your-client-id
FRAPPE_CLIENT_SECRET=your-client-secret
FRAPPE_REDIRECT_URI=https://your-app.test/auth/frappe/callback
FRAPPE_BASE_URL=https://erp.example.com
```

### Add provider event listener

#### Laravel 11+

Add the listener in your `AppServiceProvider` `boot` method:

```php
use Illuminate\Support\Facades\Event;

Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('frappe', \SocialiteProviders\Frappe\Provider::class);
});
```

<details>
<summary>Laravel 10 or below</summary>

Add the listener to the `listen` array in `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        \SocialiteProviders\Frappe\FrappeExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

```php
return Socialite::driver('frappe')->redirect();
```

The default `openid` scope is enough for login and profile data. To access the
Frappe REST API too:

```php
return Socialite::driver('frappe')
    ->scopes(['openid', 'all'])
    ->redirect();
```

### Additional User fields

Add the field names to the provider's configuration:

```php
'frappe' => [
  // ...
  'fields' => [
    'custom_department',
    'custom_employee_number',
  ],
],
```

When configured, the provider automatically requests the `all` scope and
fetches those fields from the authenticated Frappe User record. The OAuth
Client in Frappe must allow the `all` scope.

Additional fields are available in Socialite's raw user array:

```php
$user = Socialite::driver('frappe')->user();

$department = $user->getRaw()['custom_department'] ?? null;
```

### Logout

No extra configuration is required; the logout and revocation endpoints are
derived from `base_url`.

Revoke the OAuth access token and log out of your Laravel app:

```php
Socialite::driver('frappe')->revokeToken($user->token);
Auth::logout();
```

To also end the user's Frappe browser session, send the user to
`Socialite::driver('frappe')->getLogoutUrl()`.

Frappe v15 and earlier accept a `GET`, so a redirect works. Frappe v16
[restricted this endpoint to `POST`](https://github.com/frappe/frappe/commit/9c6594b47c04fd17095dd9058d2b2792dce8de26),
and `POST` is CSRF-checked; submit a form carrying the session's `csrf_token`,
or list your app's origin in the site's `allowed_referrers`.

See Frappe's official
[logout](https://docs.frappe.io/framework/user/en/guides/integration/rest_api/simple_authentication#get-api-method-logout)
and [token revocation](https://docs.frappe.io/framework/user/en/guides/integration/rest_api/oauth-2#revoke-token-endpoint)
documentation.

### Returned User fields

- `id` (OpenID `sub`, falling back to `email`)
- `name`
- `email`
- `avatar`
- `given_name`
- `family_name`
- `roles`
- Any configured `fields`, via `getRaw()`
