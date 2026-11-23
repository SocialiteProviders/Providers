<?php

namespace SocialiteProviders\Azure;

use SocialiteProviders\Manager\SocialiteWasCalled;

/**
 * @deprecated Use the Microsoft provider (socialiteproviders/microsoft) instead.
 * @see        https://github.com/SocialiteProviders/Microsoft-Azure#migrating-to-the-microsoft-provider
 */
class AzureExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('azure', Provider::class);
    }
}
