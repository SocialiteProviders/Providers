# Microsoft

```bash
composer require socialiteproviders/microsoft
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'microsoft' => [    
  'client_id' => env('MICROSOFT_CLIENT_ID'),  
  'client_secret' => env('MICROSOFT_CLIENT_SECRET'),  
  'redirect' => env('MICROSOFT_REDIRECT_URI') 
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('microsoft', \SocialiteProviders\Microsoft\Provider::class);
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
        \SocialiteProviders\Microsoft\MicrosoftExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('microsoft')->redirect();
```

## Extended features

### Tenant Details
You can also retrieve Tenant information at the same time as you retrieve users, this can be useful if you need to allow only your tenant/s or filter certain tenants.

To do this you first need to edit your `config/services.php` file and within your microsoft settings array include 'include_tenant_info' like the following:

```php
'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI'),
        'tenant' => 'common',
        'include_tenant_info' => true,
    ],
```
**NOTE: if you use `'tenant' => env('MICROSOFT_TENANT_ID')` then you should ensure that your .env file still uses 'common' as the tenant ID.**

By default this returns:
* ID
* displayName
* city
* country
* countryLetterCode
* state
* street
* verifiedDomains

Any additional fields can be returned with the attribute names detailed [here](https://learn.microsoft.com/en-us/graph/api/resources/organization?view=graph-rest-1.0).

### Refresh token
By default Microsoft doesn't return a refresh token. But if you do need a refresh token you need to add the `offline_access` scope. 
Adding the scope is done on the `redirect` method as is described in the Laravel [docs](https://laravel.com/docs/master/socialite#access-scopes).
