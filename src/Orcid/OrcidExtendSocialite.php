<?php

namespace SocialiteProviders\Orcid;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OrcidExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('orcid', Provider::class);
    }
}
