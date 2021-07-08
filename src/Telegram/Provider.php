<?php

namespace SocialiteProviders\Telegram;

use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'TELEGRAM';

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['bot'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        return null;
    }

    public function getButton()
    {
        $botname = $this->getConfig('bot');
        $callbackUrl = $this->redirectUrl;

        return sprintf(
            '<script async src="https://telegram.org/js/telegram-widget.js" data-telegram-login="%s" data-size="large" data-userpic="false" data-auth-url="%s" data-request-access="write"></script>',
            $botname,
            $callbackUrl
        );
    }

    /**
     * {@inheritdoc}
     */
    public function redirect()
    {
        return '<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />

        <title>Login using Telegram</title>
    </head>
    <body>
        '.$this->getButton().'
    </body>
</html>';
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $name = trim(sprintf('%s %s', $user['first_name'] ?? '', $user['last_name'] ?? ''));

        return (new User())->setRaw($user)->map([
            'id'        => $user['id'],
            'nickname'  => $user['username'] ?? $user['first_name'],
            'name'      => !empty($name) ? $name : null,
            'avatar'    => $user['photo_url'] ?? null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        $validator = Validator::make($this->request->all(), [
            'id'        => 'required|numeric',
            'auth_date' => 'required|date_format:U|before:1 day',
            'hash'      => 'required|size:64',
        ]);

        throw_if($validator->fails(), InvalidArgumentException::class);

        $dataToHash = collect($this->request->except('hash'))
                        ->transform(function ($val, $key) {
                            return "$key=$val";
                        })
                        ->sort()
                        ->join("\n");

        $hash_key = hash('sha256', $this->clientSecret, true);
        $hash_hmac = hash_hmac('sha256', $dataToHash, $hash_key);

        throw_if(
            $this->request->hash !== $hash_hmac,
            InvalidArgumentException::class
        );

        return $this->mapUserToObject($this->request->except(['auth_date', 'hash']));
    }
}
