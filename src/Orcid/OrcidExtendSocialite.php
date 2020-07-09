<?php

namespace SocialiteProviders\Orcid;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OrcidExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('orcid', Provider::class);
    }
}
