# SuperOffice

```bash
composer require socialiteproviders/superoffice
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'superoffice' => [    
  'client_id' => env('SUPEROFFICE_CLIENT_ID'),  
  'client_secret' => env('SUPEROFFICE_CLIENT_SECRET'),  
  'redirect' => env('SUPEROFFICE_REDIRECT_URI'),
  'environment' => env('SUPEROFFICE_ENVIRONMENT'), // can be sod, qaonline or online depending on your apps approval stage
  'customer_id' => env('SUPEROFFICE_CUSTOMER_ID') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\SuperOffice\\SuperOfficeExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('superoffice')->redirect();
```

### Returned user fields

- `id`
- `name`
- `email`
- `username`

### Reference

- [Authorization](https://community.superoffice.com/documentation/sdk/SO.NetServer.Web.Services/html/Reference-WebAPI-Authentication-Online.htm);
- [User fields](https://community.superoffice.com/documentation/sdk/SO.NetServer.Web.Services/html/v1User_GetCurrentPrincipal.htm);
- [URI variables](https://community.superoffice.com/en/developer/create-apps/overview/application-environments/);
