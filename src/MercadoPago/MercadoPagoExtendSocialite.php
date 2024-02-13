<?php

namespace SocialiteProviders\MercadoPago;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MercadoPagoExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('mercadopago', Provider::class);
    }
}
