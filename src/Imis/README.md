# IMIS

[Imis.com](https://imis.com)

```bash
composer require socialiteproviders/imis
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'imis' => [
    'host' => env('IMIS_HOST'),
    'login_url' => env('IMIS_LOGIN_URL'),
    'client_id' => env('IMIS_CLIENT_ID'),
    'client_secret' => env('IMIS_CLIENT_SECRET'),
    'redirect' => env('IMIS_CALLBACK_URL'),
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // add your listeners (aka providers) here
        'SocialiteProviders\\Imis\\ImisExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('imis')->redirect();
```


Example env
```php
IMIS_HOST=https://www.public-imis-site.com
IMIS_LOGIN_URL=Web/Sign-in.aspx
IMIS_CLIENT_ID=MySSOApp
IMIS_CLIENT_SECRET=
IMIS_CALLBACK_URL=https://example-laravel-site.com/oauth2/imis/callback
```

<hr>

### Creating the IMIS UserInfo Query

Create directory in root: 'OAuth2' and create query inside this directory.

Define > Summary Tab

- Name: userInfo

- Description:

  SSO user Info
  Built to OAuth2 Standards
  https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims

Define > Sources Tab

- Sources: UserData + PartyData
- Relations: Custom (When UserData.Party Id = PartyData.Party Id)

Define > Filters

- Property: Where PartyData.Party Id
- Function: None
- Comparison: Equal
- Value: Dynamic
- LoggedInUserKey
- Prompt: No
- Limit number of results to 1

Define > Display

- PartyData.Party Id - Alias 'sub'
- UserData.Username - Alias 'username'
- UserData.Email - Alias 'email'
- PartyData.First Name - Alias 'given_name'
- PartyData.Last Name - Alias 'family_name'

Response

```html
https://{{URL}}/api/query?QueryName=$/OAuth2/userInfo
```

```json
{
    "$type": "Asi.Soa.Core.DataContracts.PagedResult, Asi.Contracts",
    "Items": {
        "$type": "System.Collections.Generic.List`1[[System.Object, mscorlib]], mscorlib",
        "$values": [
            {
                "$type": "System.Dynamic.ExpandoObject, System.Core",
                "sub": "123456aa-UUID-0000-0000-000000000000",
                "username": "EXAMPLE@EXAMPLE.COM.AU",
                "email": "example@example.com",
                "given_name": "First",
                "family_name": "Last"
            }
        ]
    },
    "Offset": 0,
    "Limit": 100,
    "Count": 1,
    "TotalCount": 1,
    "NextPageLink": null,
    "HasNext": false,
    "NextOffset": 0
}
```
<hr>

#### Helpful tips

- [SSO Setup Info](https://blog.jamessiebert.com/laravel-socialite-imis-tutorial/)
- [Migrating from IQA to Query Service](https://developer.imis.com/docs/migrating-from-iqa-to-query-service-endpoint)
- In IMIS use the same name for the Client ID and the SSO content item
- A custom query needs to be created to return the user info, userInfo endpoints are not supported by Imis
- Imis returns a 'refresh_token' instead of the auth code so the provider has been modified to handle this.
- Imis does return values when a user is not logged in. The refresh_token and bearer token relate to a Guest user.
  As the guest user has no user attributes, we should not allow this in our laravel app.
  This is how I handle this:

    ```php
    // -- When handling a POST to the callback url
    
        public function oauthHandleCallback(Request $request, String $provider): RedirectResponse
        {
            switch ($provider) {
            
                case "imis":
    
                        // Copy 'refresh_token' to a 'code' for use in Socialite
                        $request->request->add(['code' => $request->post('refresh_token')]);
    
                        // Fails if user is a guest
                        try {
                            $user = Socialite::driver('imis')->stateless()->user();
                        }
                        catch(\Throwable $e) {
                            // Redirect to Imis login
                            return redirect()->away(config('services.imis.host').'/'.config('services.imis.login_url'));
                        }
                    break;
    
                default:
                    dd('provider fail not found');
            }
    
            $authUser = $this->findOrCreateUser($user, $provider);
    
            Auth::login($authUser, true);
    
            return redirect(config('app.url').'/member');
        }
    ```


[project setup tutorial](https://blog.jamessiebert.com/laravel-socialite-imis-tutorial)

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
- ``avatar``
- ``user[]``

