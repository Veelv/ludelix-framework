<?php

namespace Ludelix\Fluid;

class Version
{
    /**
     * Major version number
     */
    public const MAJOR = 1;

    /**
     * Minor version number
     */
    public const MINOR = 0;

    /**
     * Patch version number
     */
    public const PATCH = 0;

    /**
     * Pre-release version (e.g., 'beta', 'rc1', 'dev')
     */
    public const PRE_RELEASE = '';

    /**
     * Get the current Fluid version.
     *
     * @return string
     */
    public static function get(): string
    {
        $version = sprintf('%d.%d.%d', self::MAJOR, self::MINOR, self::PATCH);

        if (!empty(self::PRE_RELEASE)) {
            $version .= '-' . self::PRE_RELEASE;
        }

        return $version;
    }
}
