# OneID for Laravel Socialite

OneID (Uzbekistan SSO) provider for [SocialiteProviders](https://github.com/SocialiteProviders/Providers).

## Requirements

- PHP 8.1+
- Laravel 10/11+
- `laravel/socialite`
- `socialiteproviders/manager` or `aslnbxrz/oneid-socialite`

## Installation

```bash
composer require socialiteproviders/oneid
```

or

```bash
composer require aslnbxrz/oneid-socialite
```

## Configuration

Add to `config/services.php`:

```php
'oneid' => [
    'client_id'     => env('ONEID_CLIENT_ID'),
    'client_secret' => env('ONEID_CLIENT_SECRET'),
    'redirect'      => env('ONEID_REDIRECT_URI'),
    // Optional (defaults shown):
    'base_url'      => env('ONEID_BASE_URL', 'https://sso.egov.uz'),
    'scope'         => env('ONEID_SCOPE', 'one_code'),
],
```

Add to your `.env`

```dotenv
ONEID_CLIENT_ID=your-client-id
ONEID_CLIENT_SECRET=your-client-secret
ONEID_REDIRECT_URI=https://your-app.com/auth/oneid/callback
# Optional:
# ONEID_BASE_URL=https://sso.egov.uz
# ONEID_SCOPE=one_code
```

## Laravel 11+ Event Listener

Place this in a service provider boot() method (e.g. App\Providers\AppServiceProvider):

```php
use Illuminate\Support\Facades\Event;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\OneID\Provider; // or Aslnbxrz\OneID\Provider

public function boot(): void
{
    Event::listen(function (SocialiteWasCalled $event) {
        $event->extendSocialite('oneid', Provider::class);
    });
}
```

## Usage

#### Web (redirect flow)

```php
use Laravel\Socialite\Facades\Socialite;

// Redirect to OneID
Route::get('/auth/oneid/redirect', function () {
    return Socialite::driver('oneid')->redirect();
});

// Callback
Route::get('/auth/oneid/callback', function () {
    /** @var \Aslnbxrz\OneID\OneIDUser $user */
    $user = Socialite::driver('oneid')->user();

    // Standard fields
    $id    = $user->getId();
    $name  = $user->getName();
    $email = $user->getEmail();

    // Custom fields
    $pinfl    = $user->getPinfl();
    $sessId   = $user->getSessionId();
    $passport = $user->getPassport();
    $phone    = $user->getPhone();
    $gender   = $user->getGender();

    // TODO: login/register user logic
});
```

#### API (stateless mode)

OneID can be integrated into API flows (e.g. mobile apps).  
You may authenticate users by either **access_token** (already issued by OneID) or **authorization code**.

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

// --- Variant A: using access_token directly ---
Route::post('/api/auth/oneid/token', function (Request $request) {
    $validated = $request->validate([
        'access_token' => 'required|string',
    ]);

    /** @var \Aslnbxrz\OneID\OneIDUser $user */
    $user = Socialite::driver('oneid')->userFromToken($validated['access_token']);

    return response()->json([
        'id'       => $user->getId(),
        'name'     => $user->getName(),
        'email'    => $user->getEmail(),
        'pinfl'    => $user->getPinfl(),
        'sess_id'  => $user->getSessionId(),
        'passport' => $user->getPassport(),
        'phone'    => $user->getPhone(),
        'gender'   => $user->getGender(),
    ]);
});

// --- Variant B: exchanging authorization code ---
Route::post('/api/auth/oneid/code', function (Request $request) {
    $validated = $request->validate([
        'code' => 'required|string',
    ]);

    /** @var \Aslnbxrz\OneID\OneIDUser $user */
    $user = Socialite::driver('oneid')->stateless()->user();

    return response()->json([
        'id'       => $user->getId(),
        'name'     => $user->getName(),
        'email'    => $user->getEmail(),
        'pinfl'    => $user->getPinfl(),
        'sess_id'  => $user->getSessionId(),
        'passport' => $user->getPassport(),
        'phone'    => $user->getPhone(),
        'gender'   => $user->getGender(),
    ]);
});
```

## OneID Logout

In addition to logging out locally (revoking your Laravel session or API token), you may also notify OneID to invalidate
the session on their side. This is **REQUIRED** and should be done **after** your database transaction commits.

### Usage

```php
use Aslnbxrz\OneID\OneIDLogout;

// $accessTokenOrSessionId - access_token or sess_id
$success = app(OneIDLogout::class)->handle($accessTokenOrSessionId);
```

## Endpoints

- Authorize / Token / Userinfo: `https://sso.egov.uz/sso/oauth/Authorization.do`

## Returned User fields

**Standard fields**
- `id` — from `user_id` or `pin` or `sess_id`
- `name` — from `full_name` or concatenation of (`first_name` + `sur_name` + `mid_name`)
- `email` — if provided by OneID
- `avatar` — if provided (usually `null`)

**Custom OneID fields**
- `pinfl` — citizen ID (PINFL)
- `sess_id` — OneID session identifier
- `passport` — passport number (`pport_no`)
- `phone` — mobile phone number (`mob_phone_no` or `phone`)
- `gender` — derived from the first digit of PINFL (`male` if odd, `female` if even)

**Raw payload**
- `raw` — full OneID response as returned by the API (`$user->getRaw()`)

---

## License

MIT
