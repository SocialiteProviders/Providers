# Monzo OAUTH

 ```bash
 composer require socialiteproviders/monzo
 ```

 ## Installation & Basic Usage

 Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

 ### Add configuration to `config/services.php`

 ```php
 'monzo' => [
         'client_id' => env('MONZO_CLIENT_ID'),
         'client_secret' => env('MONZO_CLIENT_SECRET'),
         'redirect' => env('MONZO_REDIRECT_URI'),
 ],
 ```

 ### Add provider event listener

 Configure the package's listener to listen for `SocialiteWasCalled` events.

 Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

 ```php
 protected $listen = [
     \SocialiteProviders\Manager\SocialiteWasCalled::class => [
         // ... other providers
         \SocialiteProviders\Monzo\MonzoExtendSocialite::class.'@handle',
     ],
 ];
 ```

 ### Usage

 You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

 ```php
 return Socialite::driver('monzo')->redirect();
 ```

 ### Returned User fields

 - `id`


 ### Reference

 - [Monzo Developers](https://developers.monzo.com);
 - [OAuth authorization Docs](https://docs.monzo.com/#authentication);
