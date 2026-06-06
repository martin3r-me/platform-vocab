<?php

namespace Platform\Vocab\Services;

class AchievementCatalog
{
    public const TIER_BRONZE = 'bronze';
    public const TIER_SILVER = 'silver';
    public const TIER_GOLD = 'gold';

    public const CATEGORY_GETTING_STARTED = 'getting-started';
    public const CATEGORY_STREAK = 'streak';
    public const CATEGORY_MASTERY = 'mastery';
    public const CATEGORY_VOLUME = 'volume';
    public const CATEGORY_LISTS = 'lists';
    public const CATEGORY_QUALITY = 'quality';
    public const CATEGORY_MISC = 'misc';

    public static function definitions(): array
    {
        return [
            // Getting started
            'first_review' => [
                'name' => 'Auftakt',
                'description' => 'Erste Karte reviewed.',
                'icon' => 'sparkles',
                'tier' => self::TIER_BRONZE,
                'category' => self::CATEGORY_GETTING_STARTED,
                'sort' => 1,
            ],
            'first_enrollment' => [
                'name' => 'Erster Plan',
                'description' => 'Erste Liste aktiv zum Lernen markiert.',
                'icon' => 'bookmark',
                'tier' => self::TIER_BRONZE,
                'category' => self::CATEGORY_GETTING_STARTED,
                'sort' => 2,
            ],
            'goal_hit' => [
                'name' => 'Tagesziel',
                'description' => 'Tagesziel zum ersten Mal erreicht.',
                'icon' => 'check-circle',
                'tier' => self::TIER_BRONZE,
                'category' => self::CATEGORY_GETTING_STARTED,
                'sort' => 3,
            ],

            // Streak
            'streak_3' => [
                'name' => '3-Tage-Streak',
                'description' => '3 Tage in Folge gelernt.',
                'icon' => 'fire',
                'tier' => self::TIER_BRONZE,
                'category' => self::CATEGORY_STREAK,
                'sort' => 10,
            ],
            'streak_7' => [
                'name' => '7-Tage-Streak',
                'description' => 'Eine Woche durchgezogen.',
                'icon' => 'fire',
                'tier' => self::TIER_SILVER,
                'category' => self::CATEGORY_STREAK,
                'sort' => 11,
            ],
            'streak_14' => [
                'name' => '14-Tage-Streak',
                'description' => 'Zwei Wochen am Stück.',
                'icon' => 'fire',
                'tier' => self::TIER_SILVER,
                'category' => self::CATEGORY_STREAK,
                'sort' => 12,
            ],
            'streak_30' => [
                'name' => '30-Tage-Streak',
                'description' => 'Einen Monat ohne Pause.',
                'icon' => 'fire',
                'tier' => self::TIER_GOLD,
                'category' => self::CATEGORY_STREAK,
                'sort' => 13,
            ],
            'streak_100' => [
                'name' => '100-Tage-Streak',
                'description' => 'Wahnsinn — 100 Tage in Folge.',
                'icon' => 'fire',
                'tier' => self::TIER_GOLD,
                'category' => self::CATEGORY_STREAK,
                'sort' => 14,
            ],

            // Mastery
            'mastered_10' => [
                'name' => '10 sitzen',
                'description' => '10 Karten gemeistert.',
                'icon' => 'academic-cap',
                'tier' => self::TIER_BRONZE,
                'category' => self::CATEGORY_MASTERY,
                'sort' => 20,
            ],
            'mastered_50' => [
                'name' => '50 sitzen',
                'description' => '50 Karten gemeistert.',
                'icon' => 'academic-cap',
                'tier' => self::TIER_BRONZE,
                'category' => self::CATEGORY_MASTERY,
                'sort' => 21,
            ],
            'mastered_100' => [
                'name' => '100 sitzen',
                'description' => '100 Karten gemeistert.',
                'icon' => 'academic-cap',
                'tier' => self::TIER_SILVER,
                'category' => self::CATEGORY_MASTERY,
                'sort' => 22,
            ],
            'mastered_250' => [
                'name' => '250 sitzen',
                'description' => '250 Karten gemeistert.',
                'icon' => 'academic-cap',
                'tier' => self::TIER_SILVER,
                'category' => self::CATEGORY_MASTERY,
                'sort' => 23,
            ],
            'mastered_500' => [
                'name' => '500 sitzen',
                'description' => '500 Karten gemeistert.',
                'icon' => 'academic-cap',
                'tier' => self::TIER_GOLD,
                'category' => self::CATEGORY_MASTERY,
                'sort' => 24,
            ],
            'mastered_1000' => [
                'name' => '1000 sitzen',
                'description' => 'Vier-stellig gemeistert.',
                'icon' => 'academic-cap',
                'tier' => self::TIER_GOLD,
                'category' => self::CATEGORY_MASTERY,
                'sort' => 25,
            ],

            // Volume
            'reviews_100' => [
                'name' => '100 Reviews',
                'description' => '100 Karten reviewed (insgesamt).',
                'icon' => 'arrow-path',
                'tier' => self::TIER_BRONZE,
                'category' => self::CATEGORY_VOLUME,
                'sort' => 30,
            ],
            'reviews_500' => [
                'name' => '500 Reviews',
                'description' => '500 Karten reviewed.',
                'icon' => 'arrow-path',
                'tier' => self::TIER_SILVER,
                'category' => self::CATEGORY_VOLUME,
                'sort' => 31,
            ],
            'reviews_1000' => [
                'name' => '1000 Reviews',
                'description' => '1000 Karten reviewed.',
                'icon' => 'arrow-path',
                'tier' => self::TIER_GOLD,
                'category' => self::CATEGORY_VOLUME,
                'sort' => 32,
            ],

            // Lists
            'three_lists' => [
                'name' => 'Polyglott',
                'description' => 'In 3 Listen gleichzeitig aktiv.',
                'icon' => 'rectangle-stack',
                'tier' => self::TIER_BRONZE,
                'category' => self::CATEGORY_LISTS,
                'sort' => 40,
            ],
            'first_full_list' => [
                'name' => 'Liste durch',
                'description' => 'Eine Liste zu 100% gemeistert.',
                'icon' => 'trophy',
                'tier' => self::TIER_GOLD,
                'category' => self::CATEGORY_LISTS,
                'sort' => 41,
            ],

            // Quality
            'easy_50' => [
                'name' => 'Sitzt fest',
                'description' => '50 Karten mit „Einfach" bewertet.',
                'icon' => 'hand-thumb-up',
                'tier' => self::TIER_SILVER,
                'category' => self::CATEGORY_QUALITY,
                'sort' => 50,
            ],

            // Misc
            'comeback' => [
                'name' => 'Willkommen zurück',
                'description' => 'Nach 7+ Tagen Pause wieder gestartet.',
                'icon' => 'arrow-uturn-left',
                'tier' => self::TIER_SILVER,
                'category' => self::CATEGORY_MISC,
                'sort' => 60,
            ],
        ];
    }

    public static function get(string $code): ?array
    {
        return self::definitions()[$code] ?? null;
    }

    public static function all(): array
    {
        return self::definitions();
    }

    public static function codesForStreak(int $streak): array
    {
        $codes = [];
        if ($streak >= 3) $codes[] = 'streak_3';
        if ($streak >= 7) $codes[] = 'streak_7';
        if ($streak >= 14) $codes[] = 'streak_14';
        if ($streak >= 30) $codes[] = 'streak_30';
        if ($streak >= 100) $codes[] = 'streak_100';
        return $codes;
    }

    public static function codesForMastered(int $mastered): array
    {
        $codes = [];
        if ($mastered >= 10) $codes[] = 'mastered_10';
        if ($mastered >= 50) $codes[] = 'mastered_50';
        if ($mastered >= 100) $codes[] = 'mastered_100';
        if ($mastered >= 250) $codes[] = 'mastered_250';
        if ($mastered >= 500) $codes[] = 'mastered_500';
        if ($mastered >= 1000) $codes[] = 'mastered_1000';
        return $codes;
    }

    public static function codesForTotalReviews(int $total): array
    {
        $codes = [];
        if ($total >= 100) $codes[] = 'reviews_100';
        if ($total >= 500) $codes[] = 'reviews_500';
        if ($total >= 1000) $codes[] = 'reviews_1000';
        return $codes;
    }
}
