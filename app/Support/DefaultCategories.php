<?php

namespace App\Support;

use App\Models\User;

/**
 * The starter categories every new account gets, in English or Dutch.
 */
class DefaultCategories
{
    /** @var list<array{en: string, nl: string, type: string, color: string, icon: string, budget: int|null}> */
    public const LIST = [
        ['en' => 'Salary', 'nl' => 'Salaris', 'type' => 'income', 'color' => '#5fb88a', 'icon' => 'briefcase', 'budget' => null],
        ['en' => 'Side jobs', 'nl' => 'Bijbaan', 'type' => 'income', 'color' => '#7cc6c0', 'icon' => 'spark', 'budget' => null],
        ['en' => 'Gifts', 'nl' => 'Cadeaus', 'type' => 'income', 'color' => '#d7a8e0', 'icon' => 'gift', 'budget' => null],
        ['en' => 'Rent', 'nl' => 'Huur', 'type' => 'expense', 'color' => '#e0915f', 'icon' => 'home', 'budget' => 75000],
        ['en' => 'Groceries', 'nl' => 'Boodschappen', 'type' => 'expense', 'color' => '#e8c15a', 'icon' => 'cart', 'budget' => 30000],
        ['en' => 'Eating out', 'nl' => 'Uit eten', 'type' => 'expense', 'color' => '#e27a7a', 'icon' => 'cup', 'budget' => 10000],
        ['en' => 'Transport', 'nl' => 'Vervoer', 'type' => 'expense', 'color' => '#6fa3d8', 'icon' => 'bus', 'budget' => 8000],
        ['en' => 'Subscriptions', 'nl' => 'Abonnementen', 'type' => 'expense', 'color' => '#9b8fe0', 'icon' => 'repeat', 'budget' => 4000],
        ['en' => 'Shopping', 'nl' => 'Winkelen', 'type' => 'expense', 'color' => '#e08fb5', 'icon' => 'bag', 'budget' => 10000],
        ['en' => 'Health', 'nl' => 'Gezondheid', 'type' => 'expense', 'color' => '#6cc3a0', 'icon' => 'heart', 'budget' => null],
        ['en' => 'Fun', 'nl' => 'Uitjes', 'type' => 'expense', 'color' => '#c9a05a', 'icon' => 'ticket', 'budget' => 6000],
    ];

    public static function create(User $user, string $locale = 'en'): void
    {
        $locale = $locale === 'nl' ? 'nl' : 'en';

        foreach (self::LIST as $category) {
            $user->categories()->create([
                'name' => $category[$locale],
                'type' => $category['type'],
                'color' => $category['color'],
                'icon' => $category['icon'],
                'budget' => $category['budget'],
            ]);
        }
    }
}
