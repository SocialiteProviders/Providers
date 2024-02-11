<?php

namespace SocialiteProviders\DocuSign;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DocuSignExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('docusign', Provider::class);
    }
}
