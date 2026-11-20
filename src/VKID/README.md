# VK ID

```bash
composer require socialiteproviders/vkid
```

## Register an application 

Add new application at [vk.ru](https://id.vk.ru/about/business/go).

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'vkid' => [
  'client_id' => env('VKID_CLIENT_ID'),
  'client_secret' => env('VKID_CLIENT_SECRET'),
  'redirect' => env('VKID_REDIRECT_URI'),

  // Public apps (default): leave false. Do not send classic client_secret —
  // VK ID returns HTTP 404 for public apps when it is present.
  // Confidential apps: set true and put the *service token* in client_secret;
  // it is sent as service_token on token exchange.
  'confidential' => env('VKID_CONFIDENTIAL', false),

  // Optional PKCE cache tuning (used for both session and stateless flows)
  // 'pkce_ttl' => 10,
  // 'cache_store' => 'redis',
  // 'cache_prefix' => 'socialite:vkid:pkce:',
],
```

### Local development (VK ID)

VK ID only accepts localhost redirects on ports **80** or **443**:

- Base domain: `localhost`
- Trusted redirect URL example: `http://localhost/auth/vkid/callback` (no non-standard port in the URL)

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('vkid', \SocialiteProviders\VKID\Provider::class);
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
        \SocialiteProviders\VKID\VKIDExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('vkid')->redirect();
```

### Stateless / API / SPA

PKCE `code_verifier` is stored in the application cache (not the session), so `stateless()` works for API and SPA callbacks. VK ID still requires a `state` parameter (≥ 32 characters); this provider always sends one.

```php
return Socialite::driver('vkid')->stateless()->redirect();

$user = Socialite::driver('vkid')->stateless()->user();
```

Ensure a shared cache store is available between the redirect and callback requests (for example Redis).

### Returned User fields
- ``id``
- ``name``
- ``email``
- ``avatar``

### Reference

- [VK ID Reference](https://id.vk.ru/about/business/go/docs/ru/vkid/latest/methods)
