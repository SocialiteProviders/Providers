# Laravel Passport Provider

## HOW TO USE

.env

```ini
SERVICE_LARAVELPASSPORT_HOST=http://server.dev
SERVICE_LARAVELPASSPORT_CLIENT_ID=client_id
SERVICE_LARAVELPASSPORT_CLIENT_SECRET=client_secret
SERVICE_LARAVELPASSPORT_REDIRECT=http://client.dev/auth/laravelpassport/callback
```

app/config/service.php

```php
'laravelpassport' => [
    'host' => env('SERVICE_LARAVELPASSPORT_HOST'),
    'client_id' => env('SERVICE_LARAVELPASSPORT_CLIENT_ID'),
    'client_secret' => env('SERVICE_LARAVELPASSPORT_CLIENT_SECRET'),
    'redirect' => env('SERVICE_LARAVELPASSPORT_REDIRECT'),

    // optional
    'authorize_uri' => 'oauth/authorize', // if your authorize_uri isn't same, you can change it
    'token_uri' => 'oauth/token', // if your token_uri isn't same, you can change it
    'userinfo_uri' => 'api/user', // if your userinfo_uri isn't same, you can change it
    'userinfo_key' => '', // if your userinfo response is like {"data": {"id" => "xxx", "email" => "xxx@test.com"}} you can set userinfo_key => 'data'
]
```