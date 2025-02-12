<?php

namespace SocialiteProviders\LlaveMx;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Laravel\Socialite\Two\ProviderInterface;
use RuntimeException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    public const IDENTIFIER = 'LLAVEMX';

    private const LLAVEMX_OAUTH_ENDPOINT = '/oauth.xhtml';
    private const LLAVEMX_TOKEN_ENDPOINT = '/ws/rest/oauth/obtenerToken';
    private const LLAVEMX_USER_ENDPOINT = '/ws/rest/oauth/datosUsuario';
    private const LLAVEMX_USER_ROLES_ENDPOINT = '/ws/rest/oauth/getRolesUsuarioLogueado';

    /**
     * {@inheritdoc}
     * 
     */
    public static function additionalConfigKeys(): array
    {
        return [
            'base_url',
            'api_user',
            'api_password',
        ];
    }

    /**
     * Get the base URL.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getLlaveMxUrl()
    {
        $baseUrl = $this->getConfig('base_url');

        if ($baseUrl === null) {
            throw new InvalidArgumentException('Missing base_url');
        }

        return rtrim($baseUrl);
    }

    protected function getAuthUrl($state): string
    {
        $state = $state ?: $this->getState(); 
        
        return $this->getLlaveMxUrl() . self::LLAVEMX_OAUTH_ENDPOINT . '?' . http_build_query([
            'client_id'     => $this->clientId,
            'redirect_url'  => $this->redirectUrl,
            'state'         => $state,
        ]);
    }

    protected function getTokenUrl(): string
    {
        return $this->getLlaveMxUrl() . self::LLAVEMX_TOKEN_ENDPOINT;
    }

    /**
     * {@inheritdoc}
     * 
     */
    public function getAccessTokenResponse($code)
    {
        try {
            if (empty($code)) {
                throw new InvalidArgumentException('El código de autorización no puede estar vacío.');
            }
            
            $response = $this->getHttpClient()->post($this->getTokenUrl(), [
                RequestOptions::AUTH => $this->getApiCredentials(),
                RequestOptions::JSON => $this->getTokenFields($code),
            ]);
            
            $responseBody = json_decode($response->getBody(), true);
            
            if (!isset($responseBody['accessToken'])) {
                throw new \Exception('Error al obtener el token de acceso.');
            }
            
            $data = $responseBody;
            $data['access_token'] = Arr::pull($data, 'accessToken');
            return Arr::add($data, 'expires_in', Arr::pull($data, 'expires'));

        } catch (\Exception $e) {
            throw new RuntimeException('Error al obtener el token de acceso: ' . $e->getMessage());

        }
    }

    /**
     * {@inheritdoc}
     * 
     */
    protected function getUserByToken($token)
    {
        try {
            
            $response = $this->getHttpClient()
            ->get($this->getLlaveMxUrl() . self::LLAVEMX_USER_ENDPOINT, [
                RequestOptions::AUTH => $this->getApiCredentials(),
                RequestOptions::HEADERS => [
                    'accessToken' => $token,
                ],
            ]);

            $userData = json_decode((string) $response->getBody(), true);

            $rolesResponse = $this->getHttpClient()
            ->post($this->getLlaveMxUrl() . self::LLAVEMX_USER_ROLES_ENDPOINT, [
                RequestOptions::AUTH => $this->getApiCredentials(),
                RequestOptions::HEADERS => [
                    'accessToken' => $token
                ],
                RequestOptions::JSON => [
                    'idSistema' => $this->clientId,
                    'idUsuario' => $userData['idUsuario']
                ],
            ])->getBody();

            $rolesData = json_decode($rolesResponse, true);
            
            $userData['roles'] = $rolesData['roles'] ?? [];

            return $userData;

        } catch (\Exception $e) {
            throw new \Exception('Error al obtener los datos del usuario: ' . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     * 
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['idUsuario'], 
            'name' => $user['nombre'],
            'first_name' => $user['nombre'],
            'last_name' => "{$user['primerApellido']} {$user['segundoApellido']}",
            'nombre_completo' => "{$user['nombre']} {$user['primerApellido']} {$user['segundoApellido']}", 
            'email' => $user['correo'], 
            'curp' => $user['curp'], 
            'telefono' => $user['telVigente'] ?? null, 
            'es_extranjero' => $user['esExtranjero'], 
            'extranjero_telefono' => $user['telefonoExtranjero'] ?? null, 
            'extranjero_lada' => $user['ladaExtranjero'] ?? null, 
            'fecha_nacimiento' => $user['fechaNacimiento'], 
            'sexo' => $user['sexo'], 
            'llave_mx' => [
                'cuenta_basica' => $user['cuentaBasica'],
                'cuenta_verificada' => $user['cuentaVerificada'],
                'tiene_firma_mx' => $user['tieneFirmaMX'],
            ],
            'estado_nacimiento' => [
                'id'   => $user['idEstadoNacimiento'],
                'name' => $user['estadoNacimiento'],
            ], 
            'roles' => array_map(fn($role) => $role['rol'], $user['roles'] ?? []), 
            'domicilio' => isset($user['domicilio']) ? [
                'codigo_postal'  => $user['domicilio']['codigoPostal'],
                'colonia'       => $user['domicilio']['colonia'],
                'municipio' => $user['domicilio']['alcaldiaMunicipio'],
                'estado'        => $user['domicilio']['estado'],
                'calle'       => $user['domicilio']['calle'],
                'numero_exterior'   => $user['domicilio']['numExterior'],
                'numero_interior'   => $user['domicilio']['numInterior'],
            ] : null, 
        ]);
    }

    /**
     * {@inheritdoc}
     * 
     */
    protected function getTokenFields($code)
    {
        return [
            'grantType' => 'authorization_code',
            'code' => $code,
            'redirectUri' => $this->redirectUrl,
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
        ];
    }

    protected function getApiCredentials(): array
    {
        return [$this->getConfig('api_user'), $this->getConfig('api_password')];
    }

}