<?php

namespace SocialiteProviders\Tests\Saml2;

use LightSaml\ClaimTypes;
use LightSaml\Error\LightSamlValidationException;
use SocialiteProviders\Saml2\InvalidSignatureException;
use SocialiteProviders\Saml2\User;

class ReceiveTest extends TestCase
{
    public function test_it_resolves_a_user_from_a_posted_saml_response(): void
    {
        $request = $this->makeRequest('POST', ['SAMLResponse' => $this->samlResponse()]);

        $user = $this->makeProvider($request)->stateless()->user();

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('user@example.com', $user->getId());
        $this->assertSame('user@example.com', $user->getEmail());
        $this->assertSame('Test User', $user->getName());
        $this->assertSame('Test', $user->first_name);
        $this->assertSame('User', $user->last_name);
    }

    public function test_it_maps_configured_custom_attributes(): void
    {
        $response = $this->samlResponse([], [
            ClaimTypes::EMAIL_ADDRESS      => 'user@example.com',
            'urn:custom:employee_number'   => '12345',
        ]);

        $request = $this->makeRequest('POST', ['SAMLResponse' => $response]);

        $user = $this->makeProvider($request, [
            'attribute_map' => ['employee_number' => 'urn:custom:employee_number'],
        ])->stateless()->user();

        $this->assertSame('12345', $user->employee_number);

        $rawNames = array_map(fn ($attribute) => $attribute->getName(), $user->getRaw());
        $this->assertContains('urn:custom:employee_number', $rawNames);
    }

    public function test_it_rejects_a_response_addressed_to_an_unknown_recipient(): void
    {
        $response = $this->samlResponse(['recipient' => 'https://attacker.test/acs']);

        $request = $this->makeRequest('POST', ['SAMLResponse' => $response]);

        $this->expectException(LightSamlValidationException::class);
        $this->expectExceptionMessage('recipient endpoint');

        $this->makeProvider($request)->stateless()->user();
    }

    public function test_it_rejects_a_response_from_an_unexpected_issuer(): void
    {
        $response = $this->samlResponse(['issuer' => 'https://attacker.test/metadata']);

        $request = $this->makeRequest('POST', ['SAMLResponse' => $response]);

        $this->expectException(LightSamlValidationException::class);
        $this->expectExceptionMessage('issuer');

        $this->makeProvider($request)->stateless()->user();
    }

    public function test_it_rejects_an_unsigned_assertion(): void
    {
        $response = $this->samlResponse(['sign' => false]);

        $request = $this->makeRequest('POST', ['SAMLResponse' => $response]);

        $this->expectException(InvalidSignatureException::class);

        $this->makeProvider($request)->stateless()->user();
    }

    public function test_it_rejects_an_unsuccessful_status(): void
    {
        $response = $this->samlResponse(['status' => 'urn:oasis:names:tc:SAML:2.0:status:Requester']);

        $request = $this->makeRequest('POST', ['SAMLResponse' => $response]);

        $this->expectException(LightSamlValidationException::class);
        $this->expectExceptionMessage('unsuccessful status');

        $this->makeProvider($request)->stateless()->user();
    }
}
