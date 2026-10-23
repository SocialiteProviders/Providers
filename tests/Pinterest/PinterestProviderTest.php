<?php

namespace SocialiteProviders\Tests\Pinterest;

use GuzzleHttp\Psr7\Response;
use SocialiteProviders\Pinterest\Provider;
use SocialiteProviders\Tests\TestCase;

class PinterestProviderTest extends TestCase
{
    protected function provider(): string
    {
        return Provider::class;
    }

    public function test_user_id_is_the_stable_account_id_not_the_username(): void
    {
        $provider = $this->makeProvider(
            $this->makeRequestWithSession(['code' => 'auth-code', 'state' => 'state'], ['state' => 'state']),
            [
                new Response(200, [], json_encode(['access_token' => 'access-token', 'token_type' => 'bearer'])),
                new Response(200, [], json_encode([
                    'id'            => '549755885175',
                    'username'      => 'alice',
                    'profile_image' => 'https://i.pinimg.com/alice.jpg',
                ])),
            ]
        );

        $user = $provider->user();

        $this->assertSame('549755885175', $user->getId());
        $this->assertSame('alice', $user->getNickname());
        $this->assertSame('https://i.pinimg.com/alice.jpg', $user->getAvatar());
    }
}
