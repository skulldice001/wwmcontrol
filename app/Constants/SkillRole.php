<?php

namespace App\Constants;

class SkillRole
{
    // Roles
    public const HEALER = 'Healer';
    public const TANKER = 'Tanker';
    public const DPS = 'DPS';
    public const UNKNOWN = 'Unknown';

    // Skill Slugs
    public const PANACEA_FAN = 'panacea-fan';
    public const SOULSHADE_UMBRELLA = 'soulshade-umbrella';
    public const THUNDERCRY_BLADE = 'thundercry-blade';
    public const HEAVENQUAKER_SPEAR = 'heavenquaker-spear';

    /**
     * Get the role of a skill based on its slug.
     *
     * @param string|null $slug
     * @return string
     */
    public static function getRole(?string $slug): string
    {
        if (!$slug) {
            return self::UNKNOWN;
        }

        $healers = [
            self::PANACEA_FAN,
            self::SOULSHADE_UMBRELLA,
        ];

        $tankers = [
            self::THUNDERCRY_BLADE,
            self::HEAVENQUAKER_SPEAR,
        ];

        if (in_array($slug, $healers)) {
            return self::HEALER;
        }

        if (in_array($slug, $tankers)) {
            return self::TANKER;
        }

        return self::DPS;
    }

    /**
     * Get list of all roles
     *
     * @return array
     */
    public static function getRoles(): array
    {
        return [
            self::HEALER,
            self::TANKER,
            self::DPS,
            self::UNKNOWN,
        ];
    }

    public static function getSlugsByRole(string $role): array
    {
        switch ($role) {
            case self::HEALER:
                return [
                    self::PANACEA_FAN,
                    self::SOULSHADE_UMBRELLA,
                ];
            case self::TANKER:
                return [
                    self::THUNDERCRY_BLADE,
                    self::HEAVENQUAKER_SPEAR,
                ];
            case self::DPS:
            default:
                return [];
        }
    }
}
