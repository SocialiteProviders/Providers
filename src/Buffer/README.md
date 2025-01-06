# Buffer

```bash
composer require socialiteproviders/buffer
```

::: warning 
As of October 14th, 2019, Buffer no longer supports the registration of new developer applications. 
Applications created prior to this date will retain access to the Buffer Publish API. 
Please visit our [Changelog](https://buffer.com/developers/api/logs) page for more details.
:::

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'buffer' => [    
  'client_id' => env('BUFFER_CLIENT_ID'),  
  'client_secret' => env('BUFFER_CLIENT_SECRET'),  
  'redirect' => env('BUFFER_REDIRECT_URI') 
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('buffer', \SocialiteProviders\Buffer\Provider::class);
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
        \SocialiteProviders\Buffer\BufferExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('buffer')->redirect();
```

### Returned User fields

- ``id``
- ``name``

### Reference

- [Buffer API Reference](https://buffer.com/developers/api)
