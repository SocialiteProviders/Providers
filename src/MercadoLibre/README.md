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

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('mercadolibre', \SocialiteProviders\MercadoLibre\Provider::class);
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
        \SocialiteProviders\MercadoLibre\MercadoLibreExtendSocialite::class.'@handle',
    ],
];
```
</details>

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
