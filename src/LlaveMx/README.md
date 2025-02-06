# LlaveMx

```bash
composer require socialiteproviders/llavemx
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'llavemx' => [
    'base_url' => env('LLAVEMX_BASE_URL'),
    'client_id' => env('LLAVEMX_CLIENT_ID'),
    'client_secret' => env('LLAVEMX_CLIENT_SECRET'),
    'redirect' => env('LLAVEMX_REDIRECT_URL'),
    'api_user' => env('LLAVEMX_API_USER'),
    'api_password' => env('LLAVEMX_API_PASSWORD')
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('llavemx', \SocialiteProviders\LlaveMx\Provider::class);
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
        \SocialiteProviders\LlaveMx\LlaveMxExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('llavemx')->redirect();
```

### Returned User fields

- ``id``
- ``name``
- ``first_name``
- ``last_name``
- ``nombre_completo``
- ``email``
- ``curp``
- ``telefono``
- ``es_extranjero``
- ``extranjero_telefono``
- ``extranjero_lada``
- ``fecha_nacimiento``
- ``sexo``
- ``llave_mx``
- ``estado_nacimiento``
- ``roles``
- ``domicilio``
