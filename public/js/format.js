// Small pure helpers for money, dates and months. No DOM, so they run in Node tests too.

const LOCALES = { en: 'en-GB', nl: 'nl-NL' };

export function money(amount, currency = 'EUR', lang = 'en', { sign = false, compact = false } = {}) {
  const value = Number(amount) || 0;
  const formatted = new Intl.NumberFormat(LOCALES[lang] ?? 'en-GB', {
    style: 'currency',
    currency,
    minimumFractionDigits: compact ? 0 : 2,
    maximumFractionDigits: compact ? 0 : 2,
  }).format(Math.abs(value));
  if (value < 0) return `−${formatted}`;
  return sign && value > 0 ? `+${formatted}` : formatted;
}

export function percent(value, lang = 'en') {
  return new Intl.NumberFormat(LOCALES[lang] ?? 'en-GB', { maximumFractionDigits: 0 }).format(value) + '%';
}

/** "2026-10" for a Date (local time). */
export function monthKey(date = new Date()) {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

/** Move a "YYYY-MM" key by a number of months. */
export function shiftMonth(key, delta) {
  const [year, month] = key.split('-').map(Number);
  return monthKey(new Date(year, month - 1 + delta, 1));
}

export function monthLabel(key, lang = 'en', style = 'long') {
  const [year, month] = key.split('-').map(Number);
  return new Intl.DateTimeFormat(LOCALES[lang] ?? 'en-GB', {
    month: style,
    year: style === 'long' ? 'numeric' : undefined,
  }).format(new Date(year, month - 1, 1));
}

export function dayLabel(iso, lang = 'en') {
  const [year, month, day] = iso.split('-').map(Number);
  return new Intl.DateTimeFormat(LOCALES[lang] ?? 'en-GB', {
    weekday: 'short', day: 'numeric', month: 'short',
  }).format(new Date(year, month - 1, day));
}

export function todayIso(date = new Date()) {
  return `${monthKey(date)}-${String(date.getDate()).padStart(2, '0')}`;
}

/** Change in percent between two numbers, or null when there is nothing to compare to. */
export function change(current, previous) {
  if (!previous) return null;
  return Math.round(((current - previous) / previous) * 100);
}

/** Group transactions by their date, newest day first. */
export function groupByDay(transactions) {
  const days = new Map();
  for (const transaction of transactions) {
    if (!days.has(transaction.occurred_on)) days.set(transaction.occurred_on, []);
    days.get(transaction.occurred_on).push(transaction);
  }
  return [...days.entries()].map(([date, items]) => ({
    date,
    items,
    total: items.reduce((sum, t) => sum + (t.type === 'income' ? t.amount : -t.amount), 0),
  }));
}

/**
 * Running total of spending per day, and where spending "should" be
 * if the monthly budget were spread evenly over the month.
 */
export function spendingPace(daily, budget) {
  let running = 0;
  return daily.map((day, index) => {
    running += day.expense;
    return {
      date: day.date,
      spent: Math.round(running * 100) / 100,
      pace: budget ? Math.round((budget * (index + 1)) / daily.length * 100) / 100 : null,
    };
  });
}

/** Turn a decimal string typed by the user ("12,50" or "12.50") into a number. */
export function parseAmount(text) {
  const cleaned = String(text).trim().replace(/\s/g, '');
  if (cleaned === '') return NaN;
  const normalised = cleaned.includes(',') && !cleaned.includes('.')
    ? cleaned.replace(',', '.')
    : cleaned.replace(/,/g, '');
  const value = Number(normalised);
  return Number.isFinite(value) ? Math.round(value * 100) / 100 : NaN;
}
