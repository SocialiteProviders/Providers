# GitHub

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

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('monday')->redirect();
```

### Returned User fields

- `birthday`
- `country_code`
- `created_at`
- `join_date`
- `email`
- `enabled`
- `id`
- `is_admin`
- `is_guest`
- `is_pending`
- `is_view_only`
- `location`
- `mobile_phone`
- `name`
- `phone`
- `photo_original`
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
