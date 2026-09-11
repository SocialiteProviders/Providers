<?php

namespace SocialiteProviders\Tests\Apple;

class RedirectTest extends TestCase
{
    public function test_redirect_issues_a_nonce_and_keeps_it_in_the_session(): void
    {
        $request = $this->makeRequestWithSession();

        $response = $this->makeAppleProvider($request)->redirect();

        $params = $this->queryParams($response->getTargetUrl());
        $session = $request->session();

        $this->assertSame('form_post', $params['response_mode']);
        $this->assertSame($session->get('state'), $params['state']);
        $this->assertSame($session->get('nonce'), $params['nonce']);
        $this->assertNotEmpty($params['nonce']);
        $this->assertNotSame($params['state'], $params['nonce']);
    }

    public function test_each_redirect_issues_a_fresh_nonce(): void
    {
        $request = $this->makeRequestWithSession();
        $provider = $this->makeAppleProvider($request);

        $first = $this->queryParams($provider->redirect()->getTargetUrl())['nonce'];
        $second = $this->queryParams($provider->redirect()->getTargetUrl())['nonce'];

        $this->assertNotSame($first, $second);
        $this->assertSame($second, $request->session()->get('nonce'));
    }

    public function test_stateless_redirect_sends_neither_state_nor_nonce_by_default(): void
    {
        $response = $this->makeAppleProvider()->stateless()->redirect();

        $params = $this->queryParams($response->getTargetUrl());

        $this->assertArrayNotHasKey('state', $params);
        $this->assertArrayNotHasKey('nonce', $params);
    }

    public function test_stateless_redirect_sends_a_supplied_nonce(): void
    {
        $response = $this->makeAppleProvider()->stateless()->setNonce('caller-nonce')->redirect();

        $params = $this->queryParams($response->getTargetUrl());

        $this->assertArrayNotHasKey('state', $params);
        $this->assertSame('caller-nonce', $params['nonce']);
    }

    public function test_stateless_redirect_forwards_a_caller_supplied_state(): void
    {
        $response = $this->makeAppleProvider()
            ->stateless()
            ->setNonce('caller-nonce')
            ->with(['state' => 'caller-state'])
            ->redirect();

        $params = $this->queryParams($response->getTargetUrl());

        // The README's stateless recipe relies on state reaching Apple so it
        // can key the cached nonce on the value Apple echoes back.
        $this->assertSame('caller-state', $params['state']);
        $this->assertSame('caller-nonce', $params['nonce']);
    }
}
