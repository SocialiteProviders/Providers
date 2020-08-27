<?php

namespace SocialiteProviders\ConstantContact;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ConstantContactExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('constantcontact', Provider::class);
    }
}
