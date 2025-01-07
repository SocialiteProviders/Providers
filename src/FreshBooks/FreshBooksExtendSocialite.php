<?php

namespace SocialiteProviders\FreshBooks;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FreshBooksExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('freshbooks', Provider::class);
    }
}
