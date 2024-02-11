# MercadoPago

```bash
composer require socialiteproviders/mercadopago
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'mercadopago' => [    
  'client_id' => env('MERCADOPAGO_CLIENT_ID'),  
  'client_secret' => env('MERCADOPAGO_CLIENT_SECRET'),  
  'redirect' => env('MERCADOPAGO_REDIRECT_URI'),
  'country'  => env('MERCADOPAGO_COUNTRY', 'AR'), 
],
```

[Available countries](https://www.mercadopago.com.br/developers/en/docs/getting-started#bookmark_availability_of_solutions_in_each_country):
| Code | Country |
| ---- | ------- |
| AR | Argentina |
| BR | Brasil |
| CL | Chile |
| CO | Colombia |
| MX | México |
| PE | Perú |
| UY | Uruguay |

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\MercadoPago\MercadoPagoExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('mercadopago')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
- ``avatar``
