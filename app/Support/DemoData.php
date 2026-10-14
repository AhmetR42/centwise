<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Fills an account with six months of realistic example data,
 * so the dashboard can be tried without typing anything.
 */
class DemoData
{
    /**
     * Per starter category (by its English name): how often it happens in a month,
     * the amount range in euros, and example descriptions.
     *
     * @var array<string, array{times: array{int, int}, amount: array{int, int}, notes: list<string>, day?: int}>
     */
    private const PATTERNS = [
        'Salary' => ['times' => [1, 1], 'amount' => [1450, 1550], 'notes' => ['Salary'], 'day' => 1],
        'Side jobs' => ['times' => [0, 2], 'amount' => [60, 220], 'notes' => ['Website for a local shop', 'Laptop repair', 'Tutoring']],
        'Gifts' => ['times' => [0, 1], 'amount' => [20, 80], 'notes' => ['Birthday money']],
        'Rent' => ['times' => [1, 1], 'amount' => [650, 650], 'notes' => ['Rent'], 'day' => 1],
        'Groceries' => ['times' => [4, 6], 'amount' => [25, 85], 'notes' => ['Albert Heijn', 'Jumbo', 'Lidl', 'Market']],
        'Eating out' => ['times' => [2, 6], 'amount' => [8, 38], 'notes' => ['Lunch with classmates', 'Pizza', 'Coffee', 'Sushi', 'Kebab']],
        'Transport' => ['times' => [3, 7], 'amount' => [3, 26], 'notes' => ['NS train', 'OV-chipkaart top-up', 'Bike repair', 'Fuel']],
        'Shopping' => ['times' => [1, 3], 'amount' => [15, 120], 'notes' => ['Clothes', 'Headphones', 'Shoes', 'Bol.com order']],
        'Health' => ['times' => [0, 1], 'amount' => [10, 60], 'notes' => ['Pharmacy', 'Dentist']],
        'Fun' => ['times' => [1, 3], 'amount' => [10, 45], 'notes' => ['Cinema', 'Concert ticket', 'Bowling', 'Game']],
    ];

    /** @var list<array{string, float, int}> description, price, day of month */
    private const SUBSCRIPTIONS = [
        ['Gym', 29.99, 1],
        ['Spotify', 11.99, 5],
        ['Netflix', 13.99, 12],
        ['Phone plan', 15.00, 20],
    ];

    public static function fill(User $user, int $months = 6): void
    {
        $random = new Randomizer(new Mt19937($user->id));
        $today = Carbon::today();
        $categories = $user->categories()->get()->keyBy('name');

        // Starter categories may be in Dutch; map them back to their English key.
        $byKey = [];
        foreach (DefaultCategories::LIST as $default) {
            $byKey[$default['en']] = $categories[$default['en']] ?? $categories[$default['nl']] ?? null;
        }

        $rows = [];
        for ($offset = $months - 1; $offset >= 0; $offset--) {
            $month = $today->copy()->startOfMonth()->subMonthsNoOverflow($offset);
            $lastDay = $offset === 0 ? $today->day : $month->daysInMonth;

            foreach (self::PATTERNS as $key => $pattern) {
                $category = $byKey[$key];
                if ($category === null) {
                    continue;
                }

                $times = $random->getInt(...$pattern['times']);
                for ($i = 0; $i < $times; $i++) {
                    $day = $pattern['day'] ?? $random->getInt(1, $month->daysInMonth);
                    if ($day > $lastDay) {
                        continue;
                    }

                    [$min, $max] = $pattern['amount'];
                    $rows[] = [
                        'category_id' => $category->id,
                        'amount' => $min === $max ? $min * 100 : $random->getInt($min * 100, $max * 100),
                        'description' => $pattern['notes'][$random->getInt(0, count($pattern['notes']) - 1)],
                        'occurred_on' => $month->copy()->day($day)->toDateString(),
                    ];
                }
            }

            if ($subscriptions = $byKey['Subscriptions']) {
                foreach (self::SUBSCRIPTIONS as [$name, $price, $day]) {
                    if ($day <= $lastDay) {
                        $rows[] = [
                            'category_id' => $subscriptions->id,
                            'amount' => Money::toCents($price),
                            'description' => $name,
                            'occurred_on' => $month->copy()->day($day)->toDateString(),
                        ];
                    }
                }
            }
        }

        usort($rows, fn ($a, $b) => strcmp($a['occurred_on'], $b['occurred_on']));
        $user->transactions()->createMany($rows);

        $user->goals()->createMany([
            ['name' => 'Emergency fund', 'target' => 200000, 'saved' => 124000, 'deadline' => null, 'color' => '#5fb88a'],
            ['name' => 'New laptop', 'target' => 140000, 'saved' => 52000, 'deadline' => $today->copy()->addMonths(4)->endOfMonth()->toDateString(), 'color' => '#6fa3d8'],
            ['name' => 'Summer trip', 'target' => 90000, 'saved' => 31000, 'deadline' => $today->copy()->addMonths(8)->startOfMonth()->toDateString(), 'color' => '#e0915f'],
        ]);
    }
}
