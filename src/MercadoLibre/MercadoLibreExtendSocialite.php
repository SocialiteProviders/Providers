<?php

namespace SocialiteProviders\MercadoLibre;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MercadoLibreExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('mercadolibre', Provider::class);
    }
}
