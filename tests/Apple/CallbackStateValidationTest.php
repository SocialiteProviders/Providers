<?php

namespace SocialiteProviders\Tests\Apple;

use GuzzleHttp\Psr7\Response;
use Laravel\Socialite\Two\InvalidStateException;

class CallbackStateValidationTest extends TestCase
{
    public function test_callback_without_previously_stored_state_is_rejected(): void
    {
        $request = $this->makeRequestWithSession([
            'code'  => 'authorization-code',
            'state' => 'attacker-controlled-state',
        ]);

        $this->expectException(InvalidStateException::class);

        $this->makeAppleProvider($request)->user();
    }

    public function test_callback_with_matching_previously_stored_state_is_accepted(): void
    {
        $state = 'expected-state';
        $request = $this->makeRequestWithSession([
            'code'  => 'authorization-code',
            'state' => $state,
        ], [
            'state' => $state,
        ]);
        $provider = $this->makeAppleProvider($request);
        $provider->setHttpClient($this->makeHttpClient([
            new Response(200, [], json_encode([
                'id_token' => $this->identityToken(['nonce' => 'nonce']),
            ])),
        ]));

        $user = $provider->user();

        $this->assertSame('apple-user-id', $user->getId());
        $this->assertSame('user@example.com', $user->getEmail());
    }
}
