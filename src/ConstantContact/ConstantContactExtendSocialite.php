<?php

namespace SocialiteProviders\ConstantContact;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ConstantContactExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('constantcontact', Provider::class);
    }
}
