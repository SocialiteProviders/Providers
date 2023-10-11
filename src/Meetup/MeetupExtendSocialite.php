<?php

namespace SocialiteProviders\Meetup;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MeetupExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('meetup', Provider::class);
    }
}
