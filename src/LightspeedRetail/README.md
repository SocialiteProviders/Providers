# Lightspeed Retail

```bash
composer require socialiteproviders/lightspeedretail
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'lightspeedretail' => [    
  'client_id' => env('LIGHTSPEEDRETAIL_CLIENT_ID'),  
  'client_secret' => env('LIGHTSPEEDRETAIL_CLIENT_SECRET'),  
  'redirect' => env('LIGHTSPEEDRETAIL_REDIRECT_URI') 
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('lightspeedretail', \SocialiteProviders\LightspeedRetail\Provider::class);
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
        \SocialiteProviders\LightspeedRetail\LightspeedRetailExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('lightspeedretail')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
- ``domainPrefix`` (specific to Lightspeed Retail - store this for API calls)

### Domain Prefix Management

Lightspeed Retail uses a domain prefix (e.g., `example` in `example.retail.lightspeed.app`) that is unique to each retailer account. This prefix is returned in the OAuth response and must be stored and used for all future API calls.

```php
// Get user with domain prefix
$user = Socialite::driver('lightspeedretail')->user();
$domainPrefix = $user->domainPrefix;

// Store domain prefix with tokens for future use
$authUser = User::updateOrCreate([
    'email' => $user->email
], [
    'name' => $user->name,
    'lightspeed_token' => $user->token,
    'lightspeed_refresh_token' => $user->refreshToken,
    'lightspeed_domain_prefix' => $user->domainPrefix,
    'token_expires_at' => now()->addSeconds($user->expiresIn)
]);

// When making API calls later, set the domain prefix first
$provider = Socialite::driver('lightspeedretail')
    ->setDomainPrefix($authUser->lightspeed_domain_prefix);

// For refreshing tokens
$refreshedToken = $provider->refreshToken($authUser->lightspeed_refresh_token);
```
