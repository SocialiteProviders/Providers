<?php

use Mockery as m;
use PHPUnit\Framework\TestCase;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\LaravelPassport\Provider;

class ProviderTest extends TestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function test_authorize_uri()
    {
        $request = m::mock('Illuminate\Http\Request');
        $clientId = 'foo_client_id';
        $clientSecret = 'foo_client_secret';
        $redirectUrl = 'foo_redirect_url';
        $httpClient = m::mock('GuzzleHttp\Client');
        $config = new Config($clientId, $clientSecret, $redirectUrl, [
            'host' => 'http://foo.server.com',
            'authorize_uri' => 'foo_authorize_uri',
        ]);

        $provider = new Provider($request, $clientId, $clientSecret, $redirectUrl);
        $provider->stateless();
        $provider->setHttpClient($httpClient);
        $provider->setConfig($config);

        $response = $provider->redirect();
        $this->assertSame('http://foo.server.com/foo_authorize_uri?client_id=foo_client_id&redirect_uri=foo_redirect_url&scope=&response_type=code', $response->getTargetUrl());
    }

    public function test_token_uri()
    {
        $request = m::mock('Illuminate\Http\Request');
        $clientId = 'foo_client_id';
        $clientSecret = 'foo_client_secret';
        $redirectUrl = 'foo_redirect_url';
        $httpClient = m::mock('GuzzleHttp\Client');
        $code = 'foo_code';
        $config = new Config($clientId, $clientSecret, $redirectUrl, [
            'host' => 'http://foo.server.com',
            'authorize_uri' => 'foo_authorize_uri',
            'token_uri' => 'foo_token_uri',
            'userinfo_uri' => 'foo_userinfo_uri',
        ]);
        $provider = new Provider($request, $clientId, $clientSecret, $redirectUrl);
        $provider->stateless();
        $provider->setHttpClient($httpClient);
        $provider->setConfig($config);

        $httpClient
            ->shouldReceive('post')
            ->once()
            ->with(
                'http://foo.server.com/foo_token_uri',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'form_params' => [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'code' => $code,
                        'redirect_uri' => $redirectUrl,
                        'grant_type' => 'authorization_code',
                    ],
                ]
            )
            ->andReturn($accessTokenResponse = m::mock('stdClass'));

        $accessTokenResponse->shouldReceive('getBody')->andReturn($accessTokenResponseBody = '{"access_token": "access_token", "test": "test"}');
        $request->shouldReceive('input')->with('code')->andReturn($code);

        $httpClient
            ->shouldReceive('get')
            ->once()
            ->with(
                'http://foo.server.com/foo_userinfo_uri',
                [
                    'headers' => [
                        'Authorization' => 'Bearer access_token',
                    ],
                ]
            )
            ->andReturn($userResponse = m::mock('stdClass'));

        $userResponse->shouldReceive('getBody')->once()->andReturn('[]');

        $this->assertInstanceOf('SocialiteProviders\Manager\OAuth2\User', $provider->user());
    }
}
