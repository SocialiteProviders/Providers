# Google

```bash
composer require socialiteproviders/google
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'google' => [
  'client_id' => env('GOOGLE_CLIENT_ID'),
  'client_secret' => env('GOOGLE_CLIENT_SECRET'),
  'redirect' => env('GOOGLE_REDIRECT_URI')
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Google\\GoogleExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

The redirect method is not needed, as credential retrieval is done client-side via JavaScript.
The requirement is having the gsi/client script loaded on the page, and the g_id_onload div with the data attributes set.
In Blade, it would look something like:
```html
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <div id="g_id_onload"
         data-client_id="{{config('services.google.client_id')}}"
         data-login_uri="{{config('services.google.redirect')}}"
         data-auto_prompt="false">
    </div>
    <div class="g_id_signin"
         data-type="standard"
         data-size="large"
         data-theme="outline"
         data-text="sign_in_with"
         data-shape="rectangular"
         data-logo_alignment="left">
    </div>
```

For retrieving the user, you can use the following code in your redirect controller:

```php
$user = Socialite::driver('google')->user();
dd($user);
```
