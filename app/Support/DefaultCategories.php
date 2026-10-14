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
        ['en' => 'Salary', 'nl' => 'Salaris', 'type' => 'income', 'color' => '#2fae6a', 'icon' => 'briefcase', 'budget' => null],
        ['en' => 'Side jobs', 'nl' => 'Bijbaan', 'type' => 'income', 'color' => '#20b2aa', 'icon' => 'spark', 'budget' => null],
        ['en' => 'Gifts', 'nl' => 'Cadeaus', 'type' => 'income', 'color' => '#9a6cf0', 'icon' => 'gift', 'budget' => null],
        ['en' => 'Rent', 'nl' => 'Huur', 'type' => 'expense', 'color' => '#f08a3c', 'icon' => 'home', 'budget' => 75000],
        ['en' => 'Groceries', 'nl' => 'Boodschappen', 'type' => 'expense', 'color' => '#f5b82e', 'icon' => 'cart', 'budget' => 30000],
        ['en' => 'Eating out', 'nl' => 'Uit eten', 'type' => 'expense', 'color' => '#e8564a', 'icon' => 'cup', 'budget' => 10000],
        ['en' => 'Transport', 'nl' => 'Vervoer', 'type' => 'expense', 'color' => '#2f8fe0', 'icon' => 'bus', 'budget' => 8000],
        ['en' => 'Subscriptions', 'nl' => 'Abonnementen', 'type' => 'expense', 'color' => '#5b6cf0', 'icon' => 'repeat', 'budget' => 4000],
        ['en' => 'Shopping', 'nl' => 'Winkelen', 'type' => 'expense', 'color' => '#e75f9b', 'icon' => 'bag', 'budget' => 10000],
        ['en' => 'Health', 'nl' => 'Gezondheid', 'type' => 'expense', 'color' => '#8cc63f', 'icon' => 'heart', 'budget' => null],
        ['en' => 'Fun', 'nl' => 'Uitjes', 'type' => 'expense', 'color' => '#b08a4a', 'icon' => 'ticket', 'budget' => 6000],
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
