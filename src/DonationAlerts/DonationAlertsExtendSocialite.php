<?php

declare(strict_types=1);

namespace SocialiteProviders\DonationAlerts;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DonationAlertsExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('donationalerts', Provider::class);
    }
}
