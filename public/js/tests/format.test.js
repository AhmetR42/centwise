import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
  change, groupByDay, money, monthKey, parseAmount, shiftMonth, spendingPace, todayIso,
} from '../format.js';

test('money formats per language and keeps the sign readable', () => {
  assert.equal(money(1234.5, 'EUR', 'en'), '€1,234.50');
  assert.match(money(1234.5, 'EUR', 'nl'), /1\.234,50/);
  assert.equal(money(-5, 'EUR', 'en'), '−€5.00');
  assert.equal(money(5, 'EUR', 'en', { sign: true }), '+€5.00');
  assert.equal(money(1999.99, 'USD', 'en', { compact: true }), 'US$2,000');
});

test('month keys move across years', () => {
  assert.equal(monthKey(new Date(2026, 0, 31)), '2026-01');
  assert.equal(shiftMonth('2026-01', -1), '2025-12');
  assert.equal(shiftMonth('2026-12', 1), '2027-01');
  assert.equal(todayIso(new Date(2026, 9, 4)), '2026-10-04');
});

test('change in percent', () => {
  assert.equal(change(150, 100), 50);
  assert.equal(change(50, 100), -50);
  assert.equal(change(10, 0), null);
});

test('transactions are grouped per day with a net total', () => {
  const days = groupByDay([
    { occurred_on: '2026-10-02', type: 'expense', amount: 10 },
    { occurred_on: '2026-10-02', type: 'income', amount: 25 },
    { occurred_on: '2026-10-01', type: 'expense', amount: 4.5 },
  ]);
  assert.equal(days.length, 2);
  assert.equal(days[0].total, 15);
  assert.equal(days[1].total, -4.5);
});

test('spending pace spreads the budget over the month', () => {
  const pace = spendingPace([
    { date: 'a', expense: 10, income: 0 },
    { date: 'b', expense: 0, income: 0 },
    { date: 'c', expense: 20, income: 0 },
    { date: 'd', expense: 5, income: 0 },
  ], 100);
  assert.deepEqual(pace.map((p) => p.spent), [10, 10, 30, 35]);
  assert.deepEqual(pace.map((p) => p.pace), [25, 50, 75, 100]);
  assert.equal(spendingPace([{ date: 'a', expense: 1 }], null)[0].pace, null);
});

test('amounts typed with a comma or a dot', () => {
  assert.equal(parseAmount('12,50'), 12.5);
  assert.equal(parseAmount('12.5'), 12.5);
  assert.equal(parseAmount('1,234.56'), 1234.56);
  assert.equal(parseAmount(' 7 '), 7);
  assert.ok(Number.isNaN(parseAmount('abc')));
});
