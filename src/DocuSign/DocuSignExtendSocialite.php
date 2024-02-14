<?php

namespace SocialiteProviders\DocuSign;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DocuSignExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('docusign', Provider::class);
    }
}
