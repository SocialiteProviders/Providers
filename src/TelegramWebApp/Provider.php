<?php

namespace SocialiteProviders\TelegramWebApp;

use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'TELEGRAMWEBAPP';

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys(): array
    {
        return [];
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
        $data = $this->request->query();
        
        if (!$this->validateTelegramHash($data)) {
            throw new InvalidArgumentException('Invalid Telegram WebApp data');
        }

        $userString = $data['user'] ?? null;
        if (!$userString) {
            throw new InvalidArgumentException('User data not found in Telegram WebApp response');
        }
        
        $telegramUser = json_decode($userString, true);
        if (!$telegramUser) {
            throw new InvalidArgumentException('Invalid user data format');
        }

        return $this->mapUserToObject($telegramUser);
    }

    private function validateTelegramHash(array $data): bool
    {
        $sign = $data['hash'];

        $checkString = collect($data)
            ->except('hash')
            ->sortKeys()
            ->transform(fn ($v, $k) => "$k=$v")
            ->join("\n");
    
        $secret = hash_hmac('sha256', $this->clientSecret, 'WebAppData', true);
        $calculatedHash = bin2hex(hash_hmac('sha256', $checkString, $secret, true));

        return  hash_equals($sign, $calculatedHash);
    }
}
