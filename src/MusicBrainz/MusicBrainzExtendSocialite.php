<?php

namespace SocialiteProviders\MusicBrainz;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MusicBrainzExtendSocialite
{
    /**
     * Register the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('musicbrainz', Provider::class);
    }
}
