# Monday

```bash
composer require socialiteproviders/monday
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'monday' => [    
  'client_id' => env('MONDAY_CLIENT_ID'),  
  'client_secret' => env('MONDAY_CLIENT_SECRET'),  
  'redirect' => env('MONDAY_REDIRECT_URI') 
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('monday', \SocialiteProviders\Monday\Provider::class);
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
        \SocialiteProviders\Monday\MondayExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('monday')->redirect();
```

### Returned User fields

- `id`
- `name`
- `email`
- `avatar`

#### Raw fields

```php
Socialite::driver('monday')->user()->getRaw()
```

- `birthday`
- `country_code`
- `created_at`
- `join_date`
- `enabled`
- `is_admin`
- `is_guest`
- `is_pending`
- `is_view_only`
- `location`
- `mobile_phone`
- `phone`
- `photo_small`
- `photo_thumb`
- `photo_thumb_small`
- `photo_tiny`
- `teams`
  - `id`
  - `name`
  - `picture_url`
- `time_zone_identifier`
- `title`
- `url`
- `utc_hours_diff`
