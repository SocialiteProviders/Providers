# Azure AD B2C

```bash
composer require socialiteproviders/azureadb2c
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'azureadb2c' => [
    'client_id' => env('AADB2C_ClientId'),
    'client_secret' => env('AADB2C_ClientSecret'),
    'redirect' => env('AADB2C_RedirectUri'),
    'domain' => env('AADB2C_Domain'),
    'policy' => env('AADB2C_Policy'),
    'tenantid' => env('AADB2C_TenantId')
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\AzureADB2C\\AzureADB2CExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('azureadb2c')->redirect();
```

### Returned User fields

- ``sub``
- ``name``
