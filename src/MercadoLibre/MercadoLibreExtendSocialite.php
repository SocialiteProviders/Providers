<?php

namespace SocialiteProviders\MercadoLibre;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MercadoLibreExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('mercadolibre', Provider::class);
    }
}
