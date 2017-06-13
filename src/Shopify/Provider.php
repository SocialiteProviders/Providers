<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Contracts\Factory as Socialite;

class OAuthController extends Controller
{
    public $driver = 'shopify';

    /**
     * Redirect off to oAuth server for authentication
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirect(Request $request)
    {
        return app(Socialite::class)
            ->driver($this->driver)
            // ->setConfig($this->getConfig($request))
            ->setScopes([
                'read_script_tags',
                'write_script_tags',
            ])
            ->redirect();
    }

    /**
     * Handle the oAuth callback
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        dump($request);

        $user = app(Socialite::class)
            ->driver($this->driver)
            // ->setConfig($this->getConfig($request))
            ->user();

        dd($user);
    }

    private function getConfig($request)
    {
        $config = config('services.shopify');
        return new \SocialiteProviders\Manager\Config(
            $config['client_id'],
            $config['client_secret'],
            $config['redirect_url'],
            ['subdomain' => preg_replace('/^([^\.]*).+/', '$1', $request->get('shop'))]
        );
    }
}
