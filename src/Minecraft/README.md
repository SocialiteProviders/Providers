# Live

```bash
composer require socialiteproviders/minecraft
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.
This provider is based on the [Microsoft Authentication Scheme](https://wiki.vg/Microsoft_Authentication_Scheme) described in [this document](https://mojang-api-docs.netlify.app/authentication/msa.html#oauth-2).

### Add configuration to `config/services.php`

```php
'minecraft' => [    
  'client_id' => env('MINECRAFT_CLIENT_ID'),  
  'client_secret' => env('MINECRAFT_CLIENT_SECRET'),  
  'redirect' => env('MINECRAFT_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Minecraft\MinecraftExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('minecraft')->redirect();
```
