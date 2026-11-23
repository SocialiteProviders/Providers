<?php

namespace SocialiteProviders\Microsoft;

/**
 * The profile photo sizes of the Microsoft Graph API.
 *
 * @see https://learn.microsoft.com/en-us/graph/api/profilephoto-get
 */
final class MicrosoftAvatarSize
{
    public const SIZE_48 = '48x48';

    public const SIZE_64 = '64x64';

    public const SIZE_96 = '96x96';

    public const SIZE_120 = '120x120';

    public const SIZE_240 = '240x240';

    public const SIZE_360 = '360x360';

    public const SIZE_432 = '432x432';

    public const SIZE_504 = '504x504';

    public const SIZE_648 = '648x648';

    /**
     * @var list<self::SIZE_*>
     */
    public const ALL = [
        self::SIZE_48,
        self::SIZE_64,
        self::SIZE_96,
        self::SIZE_120,
        self::SIZE_240,
        self::SIZE_360,
        self::SIZE_432,
        self::SIZE_504,
        self::SIZE_648,
    ];
}
