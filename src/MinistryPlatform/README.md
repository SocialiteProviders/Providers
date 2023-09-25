# MinistryPlatform

```bash
composer require socialiteproviders/ministryplatform
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add base URL to `.env`

MP Requires require you to autorize against a custom URL, which you may provide as the base URL.

```bash
MP_BASE_URL="https://yourchurchdomain.com/ministryplatformapi"
MP_CLIENT_ID="INSERT-CLIENT-ID"
MP_CLIENT_SECRET="INSERT-CLIENT-SECRET"
MP_REDIRECT_URL="http://your-laravel-app.com/oauth/callback"
MP_SOCIALITE_SCOPE="http://www.thinkministry.com/dataplatform/scopes/all openid offline_access"
```

### Add configuration to `config/services.php`

```php
'MinistryPlatform' => [
  'client_id' => env('MP_CLIENT_ID'),
  'client_secret' => env('MP_CLIENT_SECRET'),
  'redirect' => env('MP_REDIRECT_URI'),
  'base_url' => env('MP_BASE_URL')
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\MinistryPlatform\MinistryPlatformExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite as Socialite;

class AuthorizationCodeGrant extends Controller
{
    public function getAuthCode()
    {   

        $authorizationCodeRequest = Socialite::driver('ministryplatform')
        ->setScopes(env('MP_SOCIALITE_SCOPE'))
        ->with([
            'response_type' => 'code',
            'client_id' => env('MP_CLIENT_ID'),
            'redirect_uri' => env('MP_REDIRECT_URL'),
            ])->redirect();

        return $authorizationCodeRequest;
    }

    public function getAccessToken(Request $request) 
    {

        $codeVerifier = $request->session()->pull('code_verifier');
        
        try
        {
            $mpuser = Socialite::driver('ministryplatform')
            ->with([
                'code_verifier' => $codeVerifier //send code_verifier for PKCE
            ])
            ->user();
        }
        catch ( \Exception $e) 
        {
            return $e;
        }

        Session::put(['userInfo' => $mpuser]);

        $user = User::where('mpid', $mpuser->id)->first();

        Auth::login($user);

        return redirect(env('MP_REDIRECT_URL'));
    }
}
```

```php
namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OauthError extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function throwError(Request $request)
    {
        return response()->json($request->session()->get('error'));
    }
}

```

```php
namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        // dd(Auth::user()->mp_token);
        try {

            Auth::logout();
    
            Session::invalidate();

            Session::flush();
        
            return redirect(env('MP_BASE_URL').'/oauth/logout');

        } catch ( \Exception ) 
        {
            return redirect('oauth/error')->with('error', "Sorry! Something went wrong. Please make sure you are logged out, then try logging in again.");
        }
    }
}
```

```php

//api.php

Route::group([
    'name' => 'AuthRoutes',
    'namespace' => 'AuthRoutes',
], function () {
    Route::get('/oauth', [AuthorizationCodeGrant::class, 'getAuthCode']);
    Route::get('/oauth/callback', [AuthorizationCodeGrant::class, 'getAccessToken']);
    Route::get('/oauth/error', [OauthError::class, 'throwError']);
    Route::get('/logout', [Logout::class, 'logout']);
});


```
