# MercadoLibre

```bash
composer require socialiteproviders/mercadolibre
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'mercadolibre' => [    
  'client_id' => env('MERCADOLIBRE_CLIENT_ID'),  
  'client_secret' => env('MERCADOLIBRE_CLIENT_SECRET'),  
  'redirect' => env('MERCADOLIBRE_REDIRECT_URI'),
  'country'  => env('MERCADOLIBRE_COUNTRY', 'AR'), 
],
```

Available countries:
| Code | Country |
| ---- | ------- |
| AR | Argentina |
| BO | Bolivia |
| BR | Brasil |
| CL | Chile |
| CO | Colombia |
| CR | Costa Rica |
| DO | Dominicana |
| EC | Ecuador |
| GT | Guatemala |
| HN | Honduras |
| MX | México |
| NI | Nicaragua |
| PA | Panamá |
| PY | Paraguay |
| PE | Perú |
| SV | Salvador |
| UY | Uruguay |
| VE | Venezuela |

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\MercadoLibre\\MercadoLibreExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('mercadolibre')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
- ``avatar``
