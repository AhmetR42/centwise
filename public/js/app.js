import { api, ApiError, download, getToken, setToken, whenUnauthorized } from './api.js';
import { donut, paceChart, ring, trendBars } from './charts.js';
import {
  change, dayLabel, groupByDay, money, monthKey, monthLabel, parseAmount, percent, shiftMonth, spendingPace, todayIso,
} from './format.js';
import { language, setLanguage, t } from './i18n.js';
import { CATEGORY_ICONS, icon } from './icons.js';

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

const COLORS = ['#f5b82e', '#f08a3c', '#e8564a', '#e75f9b', '#9a6cf0', '#5b6cf0', '#2f8fe0', '#20b2aa', '#2fae6a', '#8cc63f', '#b08a4a', '#5f6670'];
const VIEWS = ['overview', 'transactions', 'budgets', 'goals', 'settings'];
const PREFS_KEY = 'centwise:prefs';

const state = {
  user: null,
  prefs: { theme: 'light', lang: 'en', motion: 'full' },
  month: monthKey(),
  view: 'overview',
  categories: [],
  summary: null,
  trend: [],
  goals: [],
  tx: { items: [], page: 1, lastPage: 1, type: '', category: '', q: '', sort: 'newest', allTime: false },
};

/* ---------- helpers ---------- */

const escape = (text) => String(text ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const fmt = (amount, options) => money(amount, state.user?.currency ?? 'EUR', language(), options);
const calm = () => state.prefs.motion === 'reduced' || matchMedia('(prefers-reduced-motion: reduce)').matches;

function fillIcons(root = document) {
  $$('[data-icon]', root).forEach((el) => {
    if (!el.firstElementChild) el.innerHTML = icon(el.dataset.icon);
  });
}

function loadPrefs() {
  try { Object.assign(state.prefs, JSON.parse(localStorage.getItem(PREFS_KEY) || '{}')); } catch { /* ignore */ }
  if (!localStorage.getItem?.(PREFS_KEY) && navigator.language?.startsWith('nl')) state.prefs.lang = 'nl';
}

function savePrefs() {
  try { localStorage.setItem(PREFS_KEY, JSON.stringify(state.prefs)); } catch { /* ignore */ }
}

/** Animate a number from its current value to a new one. */
function countTo(el, value, formatter) {
  const from = Number(el.dataset.value ?? 0);
  el.dataset.value = value;
  if (calm() || from === value) {
    el.textContent = formatter(value);
    return;
  }
  const start = performance.now();
  const duration = 700;
  const step = (now) => {
    const k = Math.min(1, (now - start) / duration);
    const eased = 1 - (1 - k) ** 3;
    el.textContent = formatter(from + (value - from) * eased);
    if (k < 1) requestAnimationFrame(step);
  };
  requestAnimationFrame(step);
}

/** Move the sliding thumb of a segmented control to the active button. */
function setSegment(group, value, attribute = 'value') {
  const buttons = $$('button', group);
  buttons.forEach((button) => button.classList.toggle('is-active', button.dataset[attribute] === value));
  const active = buttons.find((button) => button.dataset[attribute] === value);
  const thumb = $('.segmented-thumb, .lang-thumb, .tab-thumb', group);
  if (active && thumb) {
    thumb.style.width = `${active.offsetWidth}px`;
    thumb.style.transform = `translateX(${active.offsetLeft}px)`;
  }
}

function toast(message, { action, onAction, tone = 'ok' } = {}) {
  const el = document.createElement('div');
  el.className = `toast toast-${tone}`;
  el.innerHTML = `<span>${escape(message)}</span>`;
  if (action) {
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = action;
    button.addEventListener('click', () => { onAction(); remove(); });
    el.appendChild(button);
  }
  $('#toasts').appendChild(el);
  const remove = () => {
    el.classList.add('is-leaving');
    setTimeout(() => el.remove(), 300);
  };
  setTimeout(remove, action ? 6000 : 3000);
}

function showErrors(form, error) {
  $$('[data-error-for], [data-error]', form).forEach((el) => { el.textContent = ''; });
  if (!(error instanceof ApiError)) {
    toast(t('toast.offline'), { tone: 'bad' });
    return;
  }
  const general = $('[data-error]', form);
  let shown = false;
  for (const [field, messages] of Object.entries(error.errors)) {
    const target = $(`[data-error-for="${field}"]`, form) ?? general;
    if (target) {
      target.textContent = messages[0];
      shown = true;
    }
  }
  if (!shown && general) general.textContent = error.message;
  form.classList.remove('shake');
  void form.offsetWidth;
  form.classList.add('shake');
}

function confirmDialog(text, okLabel = t('delete')) {
  const dialog = $('#confirm-dialog');
  $('#confirm-text').textContent = text;
  $('button[value="ok"]', dialog).textContent = okLabel;
  dialog.showModal();
  return new Promise((resolve) => {
    dialog.addEventListener('close', () => resolve(dialog.returnValue === 'ok'), { once: true });
  });
}

/* ---------- theme and language ---------- */

function applyTheme(theme, animate = false, origin) {
  const change = () => {
    state.prefs.theme = theme;
    document.documentElement.dataset.theme = theme;
    $('#theme-toggle').setAttribute('aria-checked', String(theme === 'dark'));
    $('meta[name="theme-color"]').content = theme === 'dark' ? '#1b1b1b' : '#f1f1ef';
    setSegment($('#setting-theme'), theme);
  };
  if (!animate || calm() || !document.startViewTransition) {
    change();
    return;
  }
  const x = origin?.x ?? innerWidth - 120;
  const y = origin?.y ?? 40;
  document.documentElement.style.setProperty('--reveal-x', `${x}px`);
  document.documentElement.style.setProperty('--reveal-y', `${y}px`);
  document.startViewTransition(change);
}

function applyLanguage(lang) {
  state.prefs.lang = lang;
  setLanguage(lang);
  $$('.lang-toggle').forEach((group) => setSegment(group, lang, 'lang'));
  setSegment($('#setting-lang'), lang);
  fillCategoryFilter();
  if (state.user) renderAll();
  else if (!$('#auth').hidden) startTyping();
}

function applyMotion(motion) {
  state.prefs.motion = motion;
  if (motion === 'reduced') document.documentElement.dataset.motion = 'reduced';
  else delete document.documentElement.dataset.motion;
  setSegment($('#setting-motion'), motion);
}

/* ---------- auth ---------- */

let typingTimer;

/** Type the words of the hero title one by one, then delete them again. */
function startTyping() {
  clearTimeout(typingTimer);
  const el = $('#typed');
  const words = t('auth.typed').split('|');
  if (calm()) {
    el.textContent = words[0];
    return;
  }
  let word = 0;
  let length = 0;
  let deleting = false;
  const tick = () => {
    if ($('#auth').hidden) return;
    const text = words[word];
    length += deleting ? -1 : 1;
    el.textContent = text.slice(0, length);
    let delay = deleting ? 35 : 75;
    if (!deleting && length === text.length) {
      deleting = true;
      delay = 2200;
    } else if (deleting && length === 0) {
      deleting = false;
      word = (word + 1) % words.length;
      delay = 350;
    }
    typingTimer = setTimeout(tick, delay);
  };
  tick();
}

/** Fade sections of the landing page in as they scroll into view. */
function revealOnScroll() {
  const targets = $$('.auth .rule-title, .auth .steps li, .auth .features li, .auth .auth-card, .auth .cta-text');
  if (!('IntersectionObserver' in window) || calm()) {
    targets.forEach((el) => el.classList.add('is-visible'));
    return;
  }
  const observer = new IntersectionObserver((entries) => {
    for (const entry of entries) {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    }
  }, { threshold: 0.2 });
  targets.forEach((el, index) => {
    el.classList.add('reveal');
    el.style.setProperty('--i', index % 3);
    observer.observe(el);
  });
}

function showAuth() {
  state.user = null;
  $('#app').hidden = true;
  $('#auth').hidden = false;
  startTyping();
  revealOnScroll();
  requestAnimationFrame(() => {
    $$('.lang-toggle').forEach((group) => setSegment(group, state.prefs.lang, 'lang'));
    setSegment($('.tabs'), $('.tab.is-active').dataset.authTab, 'authTab');
  });
}

async function enterApp(user, { welcome = false } = {}) {
  state.user = user;
  $('#auth').hidden = true;
  $('#app').hidden = false;
  $('#user-name').textContent = user.name;
  $('#avatar').textContent = user.name.trim().charAt(0).toUpperCase();
  $('#demo-banner').hidden = !user.is_demo;
  $('#profile-form').name.value = user.name;
  $('#profile-form').currency.value = user.currency;
  $('#tx-currency').textContent = fmt(0).replace(/[\d.,\s−-]/g, '') || '€';
  if (welcome) toast(t('toast.welcome', { name: user.name }));
  route();
  requestAnimationFrame(() => $$('.lang-toggle').forEach((group) => setSegment(group, state.prefs.lang, 'lang')));
  await loadAll();
}

function initAuth() {
  $$('[data-auth-tab]').forEach((tab) => tab.addEventListener('click', () => {
    $$('[data-auth-tab]').forEach((other) => other.classList.toggle('is-active', other === tab));
    setSegment($('.tabs'), tab.dataset.authTab, 'authTab');
    $('#login-form').hidden = tab.dataset.authTab !== 'login';
    $('#register-form').hidden = tab.dataset.authTab !== 'register';
  }));

  const submit = (form, path, extra = {}) => form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = $('button', form);
    button.disabled = true;
    try {
      const data = await api(path, { method: 'POST', body: { ...Object.fromEntries(new FormData(form)), ...extra() } });
      setToken(data.token);
      form.reset();
      await enterApp(data.user, { welcome: true });
    } catch (error) {
      showErrors(form, error);
    } finally {
      button.disabled = false;
    }
  });
  submit($('#login-form'), '/auth/login', () => ({}));
  submit($('#register-form'), '/auth/register', () => ({ locale: language() }));

  $('#demo-button').addEventListener('click', async (event) => {
    const button = event.currentTarget;
    button.disabled = true;
    button.classList.add('is-loading');
    try {
      const data = await api('/auth/demo', { method: 'POST', body: { locale: language() } });
      setToken(data.token);
      await enterApp(data.user, { welcome: true });
    } catch {
      toast(t('toast.offline'), { tone: 'bad' });
    } finally {
      button.disabled = false;
      button.classList.remove('is-loading');
    }
  });

  whenUnauthorized(showAuth);
}

async function logout() {
  try { await api('/auth/logout', { method: 'POST' }); } catch { /* token may already be gone */ }
  setToken(null);
  showAuth();
}

/* ---------- data ---------- */

async function loadAll() {
  $('#app').classList.add('is-loading');
  try {
    const [categories, summary, trend, goals] = await Promise.all([
      api('/categories'),
      api('/stats/summary', { query: { month: state.month } }),
      api('/stats/trend', { query: { months: 6 } }),
      api('/goals'),
    ]);
    state.categories = categories.data;
    state.summary = summary;
    state.trend = trend.data;
    state.goals = goals.data;
    fillCategoryFilter();
    renderAll();
    if (state.view === 'transactions') await loadTransactions(true);
  } catch (error) {
    if (!(error instanceof ApiError)) toast(t('toast.offline'), { tone: 'bad' });
  } finally {
    $('#app').classList.remove('is-loading');
  }
}

async function loadSummary() {
  const [summary, trend] = await Promise.all([
    api('/stats/summary', { query: { month: state.month } }),
    api('/stats/trend', { query: { months: 6 } }),
  ]);
  state.summary = summary;
  state.trend = trend.data;
}

function renderAll() {
  $('#month-label').textContent = monthLabel(state.month, language());
  $('[data-month="1"]').disabled = state.month >= monthKey();
  if (!state.summary) return;
  renderOverview();
  renderBudgets();
  renderGoals();
  if (state.view === 'transactions') renderTransactions();
}

/* ---------- overview ---------- */

function changeText(current, previous, goodWhenUp) {
  const delta = change(current, previous);
  if (delta === null) return `<span class="muted">${t('overview.noCompare')}</span>`;
  const up = delta >= 0;
  const good = up === goodWhenUp;
  return `<span class="trend ${good ? 'is-good' : 'is-bad'}">${icon(up ? 'up' : 'down', 'icon icon-xs')}${Math.abs(delta)}%</span> <span class="muted">${t('overview.vsLast')}</span>`;
}

function renderOverview() {
  const s = state.summary;
  countTo($('#stat-balance'), s.balance, (v) => fmt(v));
  countTo($('#stat-income'), s.income, (v) => fmt(v));
  countTo($('#stat-expense'), s.expense, (v) => fmt(v));
  const rate = s.savings_rate ?? 0;
  countTo($('#stat-rate'), rate, (v) => percent(v, language()));
  $('#stat-rate-bar').style.width = `${Math.max(0, Math.min(100, rate))}%`;
  $('#stat-balance').classList.toggle('is-negative', s.balance < 0);
  $('#stat-count').textContent = t('overview.transactionsCount', { count: s.transactions_count });
  $('#stat-income-change').innerHTML = changeText(s.income, s.previous.income, true);
  $('#stat-expense-change').innerHTML = changeText(s.expense, s.previous.expense, false);

  // Spending pace
  const pace = spendingPace(s.daily, s.budget_total);
  paceChart($('#pace-chart'), pace, {
    format: (v, short) => fmt(v, { compact: short }),
    label: (p) => `<b>${dayLabel(p.date, language())}</b><span>${fmt(p.spent)}</span>`,
  });
  const today = todayIso();
  const reference = pace.filter((p) => p.date <= today).at(-1) ?? pace.at(-1);
  const note = $('#pace-note');
  if (!s.budget_total) {
    note.textContent = t('overview.paceNone');
    note.className = 'pace-note';
  } else {
    const diff = reference.spent - reference.pace;
    note.textContent = t(diff > 0 ? 'overview.paceAhead' : 'overview.paceBehind', { amount: fmt(Math.abs(diff)) });
    note.className = `pace-note ${diff > 0 ? 'is-bad' : 'is-good'}`;
  }

  // Donut
  const slices = s.categories
    .filter((c) => c.type === 'expense' && c.total > 0)
    .sort((a, b) => b.total - a.total)
    .map((c) => ({ ...c, value: c.total }));
  const centerDefault = slices.length
    ? `<small>${t('overview.expense')}</small><strong>${fmt(s.expense, { compact: true })}</strong>`
    : `<small>${t('overview.nothing')}</small>`;
  const center = donut($('#donut'), slices, {
    center: centerDefault,
    onSelect: (slice) => {
      center.innerHTML = slice
        ? `<small>${escape(slice.name)}</small><strong>${fmt(slice.value, { compact: true })}</strong><small>${percent((slice.value / s.expense) * 100, language())}</small>`
        : centerDefault;
      $$('#donut-legend li').forEach((li) => li.classList.toggle('is-dim', !!slice && Number(li.dataset.id) !== slice.id));
    },
  });
  $('#donut-legend').innerHTML = slices.slice(0, 6).map((c) => `
    <li data-id="${c.id}"><span class="swatch" style="--c:${c.color}"></span><span>${escape(c.name)}</span><b>${fmt(c.total, { compact: true })}</b></li>
  `).join('');

  // Trend
  trendBars($('#trend-chart'), state.trend.map((m) => ({ ...m, label: monthLabel(m.month, language(), 'short') })), {
    format: (v, short) => fmt(v, { compact: short }),
    label: (m) => `<b>${monthLabel(m.month, language())}</b><span class="pos">+${fmt(m.income)}</span><span class="neg">−${fmt(m.expense)}</span>`,
  });

  // Budgets with the highest use first
  const budgets = s.categories.filter((c) => c.budget).sort((a, b) => b.budget_used - a.budget_used).slice(0, 5);
  $('#budget-mini').innerHTML = budgets.map((c) => budgetBar(c, true)).join('') || `<li class="muted">${t('overview.paceNone')}</li>`;

  // Recent
  loadRecent();

  // Goals
  $('#goal-mini').innerHTML = state.goals.slice(0, 3).map((g) => `
    <li>${ring(g.progress, g.color, 52)}<div><b>${escape(g.name)}</b><span class="muted">${fmt(g.saved, { compact: true })} / ${fmt(g.target, { compact: true })}</span></div><span class="goal-pct">${percent(g.progress, language())}</span></li>
  `).join('') || `<li class="muted">${t('goals.empty')}</li>`;
}

async function loadRecent() {
  const data = await api('/transactions', { query: { per_page: 6 } });
  $('#recent-list').innerHTML = data.data.map(txRow).join('') || `<li class="muted">${t('overview.nothing')}</li>`;
}

function budgetBar(category, compact = false) {
  const used = category.budget_used ?? 0;
  const left = category.budget - category.total;
  const tone = used >= 100 ? 'is-over' : used >= 80 ? 'is-close' : '';
  return `
    <li class="budget ${tone}" data-category="${category.id}">
      <span class="cat-icon" style="--c:${category.color}">${icon(category.icon)}</span>
      <div class="budget-body">
        <div class="budget-top"><b>${escape(category.name)}</b><span>${fmt(category.total, { compact })} <span class="muted">/ ${fmt(category.budget, { compact: true })}</span></span></div>
        <div class="bar"><span style="--w:${Math.min(100, used)}%; --c:${category.color}"></span></div>
        ${compact ? '' : `<span class="budget-left">${left >= 0 ? t('budgets.left', { amount: fmt(left) }) : t('budgets.over', { amount: fmt(-left) })}</span>`}
      </div>
    </li>`;
}

function txRow(tx) {
  const sign = tx.type === 'income' ? 1 : -1;
  return `
    <li class="tx" data-tx="${tx.id}" tabindex="0">
      <span class="cat-icon" style="--c:${tx.category.color}">${icon(tx.category.icon)}</span>
      <div class="tx-body"><b>${escape(tx.description || tx.category.name)}</b><span class="muted">${escape(tx.category.name)} · ${dayLabel(tx.occurred_on, language())}</span></div>
      <span class="tx-amount ${sign > 0 ? 'pos' : ''}">${fmt(sign * tx.amount, { sign: true })}</span>
    </li>`;
}

/* ---------- transactions ---------- */

function fillCategoryFilter() {
  const select = $('#tx-category');
  const current = select.value;
  select.innerHTML = `<option value="">${t('tx.allCategories')}</option>` + state.categories
    .map((c) => `<option value="${c.id}">${escape(c.name)}</option>`).join('');
  select.value = current;
}

async function loadTransactions(reset = false) {
  const f = state.tx;
  if (reset) {
    f.page = 1;
    f.items = [];
  }
  const data = await api('/transactions', {
    query: {
      month: f.allTime ? '' : state.month,
      type: f.type,
      category_id: f.category,
      q: f.q,
      sort: f.sort,
      page: f.page,
      per_page: 40,
    },
  });
  f.items = reset ? data.data : [...f.items, ...data.data];
  f.lastPage = data.meta.last_page;
  f.total = data.meta.total;
  renderTransactions();
}

function renderTransactions() {
  const f = state.tx;
  const byDate = f.sort === 'newest' || f.sort === 'oldest';
  const today = todayIso();
  const yesterday = todayIso(new Date(Date.now() - 864e5));
  const label = (date) => (date === today ? t('tx.today') : date === yesterday ? t('tx.yesterday') : dayLabel(date, language()));

  if (byDate) {
    $('#tx-days').innerHTML = groupByDay(f.items).map((day, index) => `
      <section class="day" style="--i:${Math.min(index, 8)}">
        <header class="day-head"><span>${label(day.date)}</span><span class="${day.total > 0 ? 'pos' : ''}">${fmt(day.total, { sign: true })}</span></header>
        <ul class="tx-list">${day.items.map(txRow).join('')}</ul>
      </section>`).join('');
  } else {
    $('#tx-days').innerHTML = `<section class="day"><ul class="tx-list">${f.items.map(txRow).join('')}</ul></section>`;
  }

  const income = f.items.filter((x) => x.type === 'income').reduce((sum, x) => sum + x.amount, 0);
  const expense = f.items.filter((x) => x.type === 'expense').reduce((sum, x) => sum + x.amount, 0);
  $('#tx-summary').innerHTML = f.items.length
    ? `<span>${t('overview.transactionsCount', { count: f.total })}</span><span class="pos">+${fmt(income)}</span><span>−${fmt(expense)}</span>`
    : '';
  $('#tx-empty').hidden = f.items.length > 0;
  $('#tx-more').hidden = f.page >= f.lastPage;
}

function initTransactions() {
  let timer;
  $('#tx-search').addEventListener('input', (event) => {
    clearTimeout(timer);
    timer = setTimeout(() => { state.tx.q = event.target.value.trim(); loadTransactions(true); }, 250);
  });
  $$('#tx-type button').forEach((button) => button.addEventListener('click', () => {
    state.tx.type = button.dataset.type;
    setSegment($('#tx-type'), button.dataset.type, 'type');
    loadTransactions(true);
  }));
  $('#tx-category').addEventListener('change', (event) => { state.tx.category = event.target.value; loadTransactions(true); });
  $('#tx-sort').addEventListener('change', (event) => { state.tx.sort = event.target.value; loadTransactions(true); });
  $('#tx-all-time').addEventListener('change', (event) => { state.tx.allTime = event.target.checked; loadTransactions(true); });
  $('#tx-more').addEventListener('click', () => { state.tx.page += 1; loadTransactions(); });
  $('#tx-export').addEventListener('click', () => {
    const month = state.tx.allTime ? '' : state.month;
    download('/transactions/export', { month, type: state.tx.type, category_id: state.tx.category, q: state.tx.q }, `centwise-${month || 'all'}.csv`);
  });

  // Open any transaction row for editing
  document.addEventListener('click', async (event) => {
    const row = event.target.closest('[data-tx]');
    if (!row) return;
    const { data } = await api(`/transactions/${row.dataset.tx}`);
    openTransaction(data);
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && event.target.matches?.('[data-tx]')) event.target.click();
  });
}

/* ---------- transaction dialog ---------- */

let editingTx = null;
let txType = 'expense';
let txCategory = null;

function renderCategoryPicker() {
  const options = state.categories.filter((c) => c.type === txType);
  if (!options.some((c) => c.id === txCategory)) txCategory = options[0]?.id ?? null;
  $('#tx-categories').innerHTML = options.map((c) => `
    <button type="button" class="cat-option ${c.id === txCategory ? 'is-active' : ''}" data-id="${c.id}" style="--c:${c.color}">
      <span class="cat-icon">${icon(c.icon)}</span><span>${escape(c.name)}</span>
    </button>`).join('');
}

function openTransaction(tx = null) {
  editingTx = tx;
  const form = $('#tx-form');
  form.reset();
  $$('[data-error-for]', form).forEach((el) => { el.textContent = ''; });
  txType = tx?.type ?? 'expense';
  txCategory = tx?.category.id ?? null;
  $('#tx-dialog-title').textContent = t(tx ? 'editTransaction' : 'newTransaction');
  form.amount.value = tx ? String(tx.amount) : '';
  form.occurred_on.value = tx?.occurred_on ?? todayIso();
  form.description.value = tx?.description ?? '';
  $('#tx-delete').hidden = !tx;
  $('#tx-dialog').dataset.type = txType;
  $('#tx-dialog').showModal();
  setSegment($('#tx-form-type'), txType);
  renderCategoryPicker();
  form.amount.focus();
}

function initTransactionDialog() {
  const dialog = $('#tx-dialog');
  const form = $('#tx-form');

  $$('#tx-form-type button').forEach((button) => button.addEventListener('click', () => {
    txType = button.dataset.value;
    dialog.dataset.type = txType;
    setSegment($('#tx-form-type'), txType);
    renderCategoryPicker();
  }));
  $('#tx-categories').addEventListener('click', (event) => {
    const option = event.target.closest('.cat-option');
    if (!option) return;
    txCategory = Number(option.dataset.id);
    $$('.cat-option', $('#tx-categories')).forEach((el) => el.classList.toggle('is-active', el === option));
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const amount = parseAmount(form.amount.value);
    const body = {
      category_id: txCategory,
      amount: Number.isNaN(amount) ? form.amount.value : amount,
      occurred_on: form.occurred_on.value,
      description: form.description.value.trim() || null,
    };
    const before = state.summary?.categories.find((c) => c.id === txCategory)?.budget_used ?? 0;
    try {
      if (editingTx) await api(`/transactions/${editingTx.id}`, { method: 'PUT', body });
      else await api('/transactions', { method: 'POST', body });
      dialog.close();
      toast(t('toast.saved'));
      if (body.occurred_on.slice(0, 7) !== state.month && !editingTx) state.month = body.occurred_on.slice(0, 7);
      await refresh();
      const after = state.summary.categories.find((c) => c.id === txCategory);
      if (after?.budget_used >= 100 && before < 100) {
        toast(t('toast.overBudget', { category: after.name }), { tone: 'warn' });
      }
    } catch (error) {
      showErrors(form, error);
    }
  });

  $('#tx-delete').addEventListener('click', async () => {
    const tx = editingTx;
    await api(`/transactions/${tx.id}`, { method: 'DELETE' });
    dialog.close();
    await refresh();
    toast(t('toast.deleted'), {
      action: t('toast.undo'),
      onAction: async () => {
        await api('/transactions', {
          method: 'POST',
          body: { category_id: tx.category.id, amount: tx.amount, occurred_on: tx.occurred_on, description: tx.description },
        });
        await refresh();
      },
    });
  });
}

async function refresh() {
  await loadSummary();
  const goals = await api('/goals');
  state.goals = goals.data;
  renderAll();
  if (state.view === 'transactions') await loadTransactions(true);
}

/* ---------- budgets and categories ---------- */

function renderBudgets() {
  const s = state.summary;
  const expenses = s.categories.filter((c) => c.type === 'expense');
  const withBudget = expenses.filter((c) => c.budget);
  const total = withBudget.reduce((sum, c) => sum + c.budget, 0);
  const spent = withBudget.reduce((sum, c) => sum + c.total, 0);
  const used = total ? Math.round((spent / total) * 100) : 0;

  $('#budget-total').innerHTML = `
    <div><span class="muted">${t('budgets.spent')}</span><strong>${fmt(spent)}</strong></div>
    <div class="budget-total-bar"><div class="bar bar-big"><span style="--w:${Math.min(100, used)}%"></span></div><span class="muted">${percent(used, language())}</span></div>
    <div class="right"><span class="muted">${t('budgets.total')}</span><strong>${fmt(total)}</strong></div>`;

  const sorted = [...expenses].sort((a, b) => (b.budget ? 1 : 0) - (a.budget ? 1 : 0) || (b.budget_used ?? 0) - (a.budget_used ?? 0));
  $('#budget-list').innerHTML = sorted.map((c) => (c.budget ? budgetBar(c) : `
    <li class="budget is-empty" data-category="${c.id}">
      <span class="cat-icon" style="--c:${c.color}">${icon(c.icon)}</span>
      <div class="budget-body">
        <div class="budget-top"><b>${escape(c.name)}</b><span>${fmt(c.total)}</span></div>
        <span class="budget-left">${t('budgets.noBudget')} · <span class="link-btn">${t('budgets.setBudget')}</span></span>
      </div>
    </li>`)).join('');

  $('#income-categories').innerHTML = s.categories.filter((c) => c.type === 'income').map((c) => `
    <li><button type="button" class="chip" data-category="${c.id}" style="--c:${c.color}">${icon(c.icon)}<span>${escape(c.name)}</span><b class="pos">${fmt(c.total, { compact: true })}</b></button></li>
  `).join('');
}

let editingCategory = null;
let categoryType = 'expense';
let categoryIcon = 'dot';
let categoryColor = COLORS[0];

function colorPicker(container, selected) {
  container.innerHTML = COLORS.map((color) => `
    <button type="button" class="color ${color === selected ? 'is-active' : ''}" data-color="${color}" style="--c:${color}" aria-label="${color}"></button>`).join('');
}

function openCategory(category = null) {
  editingCategory = category;
  const form = $('#category-form');
  form.reset();
  $$('[data-error-for]', form).forEach((el) => { el.textContent = ''; });
  categoryType = category?.type ?? 'expense';
  categoryIcon = category?.icon ?? 'dot';
  categoryColor = category?.color ?? COLORS[state.categories.length % COLORS.length];
  $('#category-dialog-title').textContent = t(category ? 'editCategory' : 'newCategory');
  form.name.value = category?.name ?? '';
  form.budget.value = category?.budget ?? '';
  $('#category-delete').hidden = !category;
  $('#budget-field').hidden = categoryType !== 'expense';
  $('#icon-picker').innerHTML = CATEGORY_ICONS.map((name) => `
    <button type="button" class="icon-option ${name === categoryIcon ? 'is-active' : ''}" data-icon-name="${name}" aria-label="${name}">${icon(name)}</button>`).join('');
  colorPicker($('#color-picker'), categoryColor);
  $('#category-dialog').style.setProperty('--c', categoryColor);
  $('#category-dialog').showModal();
  setSegment($('#category-form-type'), categoryType);
}

function initCategoryDialog() {
  const dialog = $('#category-dialog');
  const form = $('#category-form');

  $('#add-category').addEventListener('click', () => openCategory());
  document.addEventListener('click', (event) => {
    const target = event.target.closest('[data-category]');
    if (!target) return;
    const category = state.categories.find((c) => c.id === Number(target.dataset.category));
    if (category) openCategory(category);
  });

  $$('#category-form-type button').forEach((button) => button.addEventListener('click', () => {
    categoryType = button.dataset.value;
    setSegment($('#category-form-type'), categoryType);
    $('#budget-field').hidden = categoryType !== 'expense';
  }));
  $('#icon-picker').addEventListener('click', (event) => {
    const option = event.target.closest('[data-icon-name]');
    if (!option) return;
    categoryIcon = option.dataset.iconName;
    $$('.icon-option', $('#icon-picker')).forEach((el) => el.classList.toggle('is-active', el === option));
  });
  $('#color-picker').addEventListener('click', (event) => {
    const option = event.target.closest('[data-color]');
    if (!option) return;
    categoryColor = option.dataset.color;
    dialog.style.setProperty('--c', categoryColor);
    $$('.color', $('#color-picker')).forEach((el) => el.classList.toggle('is-active', el === option));
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const budget = form.budget.value.trim() === '' ? null : parseAmount(form.budget.value);
    const body = {
      name: form.name.value.trim(),
      type: categoryType,
      icon: categoryIcon,
      color: categoryColor,
      budget: categoryType === 'expense' ? budget : null,
    };
    try {
      if (editingCategory) await api(`/categories/${editingCategory.id}`, { method: 'PATCH', body });
      else await api('/categories', { method: 'POST', body });
      dialog.close();
      toast(t('toast.saved'));
      await loadAll();
    } catch (error) {
      showErrors(form, error);
    }
  });

  $('#category-delete').addEventListener('click', async () => {
    const category = editingCategory;
    try {
      await api(`/categories/${category.id}`, { method: 'DELETE' });
    } catch (error) {
      if (error.status !== 409) throw error;
      dialog.close();
      if (!await confirmDialog(t('category.deleteUsed', { count: error.body.transactions_count }))) return;
      await api(`/categories/${category.id}`, { method: 'DELETE', query: { with_transactions: 1 } });
    }
    if (dialog.open) dialog.close();
    toast(t('toast.deleted'));
    await loadAll();
  });
}

/* ---------- goals ---------- */

function renderGoals() {
  const lang = language();
  const date = (iso) => new Intl.DateTimeFormat(lang === 'nl' ? 'nl-NL' : 'en-GB', { month: 'short', year: 'numeric' }).format(new Date(iso));
  $('#goal-grid').innerHTML = state.goals.map((g, index) => `
    <article class="goal panel ${g.progress >= 100 ? 'is-done' : ''}" style="--c:${g.color}; --i:${index}">
      <header class="goal-head">
        ${ring(g.progress, g.color, 88)}
        <span class="goal-pct-big">${percent(g.progress, lang)}</span>
        <button type="button" class="icon-btn" data-goal-edit="${g.id}" title="${t('edit')}">${icon('pencil')}</button>
      </header>
      <h3>${escape(g.name)}</h3>
      <p class="goal-amount"><strong>${fmt(g.saved)}</strong> <span class="muted">${t('goals.saved', { target: fmt(g.target) })}</span></p>
      <p class="goal-plan">${g.progress >= 100 ? `🎉 ${t('goals.reached')}` : g.deadline ? t('goals.perMonth', { amount: fmt(g.monthly_needed), date: date(g.deadline) }) : t('goals.noDeadline')}</p>
      <div class="goal-actions">
        <div class="quick">
          ${[10, 25, 50].map((amount) => `<button type="button" class="chip" data-goal-deposit="${g.id}" data-amount="${amount}">+${fmt(amount, { compact: true })}</button>`).join('')}
        </div>
        <button type="button" class="chip" data-goal-deposit="${g.id}" data-amount="-25">−${fmt(25, { compact: true })}</button>
      </div>
    </article>`).join('') + `
    <button type="button" class="goal goal-new" id="goal-new">${icon('plus')}<span>${t('goals.add')}</span></button>`;
}

let editingGoal = null;
let goalColor = COLORS[8];

function openGoal(goal = null) {
  editingGoal = goal;
  const form = $('#goal-form');
  form.reset();
  $$('[data-error-for]', form).forEach((el) => { el.textContent = ''; });
  goalColor = goal?.color ?? COLORS[(state.goals.length * 3 + 6) % COLORS.length];
  $('#goal-dialog-title').textContent = t(goal ? 'editGoal' : 'newGoal');
  form.name.value = goal?.name ?? '';
  form.target.value = goal?.target ?? '';
  form.saved.value = goal?.saved ?? '';
  form.deadline.value = goal?.deadline ?? '';
  $('#goal-delete').hidden = !goal;
  colorPicker($('#goal-color-picker'), goalColor);
  $('#goal-dialog').showModal();
}

function initGoals() {
  const dialog = $('#goal-dialog');
  const form = $('#goal-form');

  $('#add-goal').addEventListener('click', () => openGoal());
  $('#goal-grid').addEventListener('click', async (event) => {
    if (event.target.closest('#goal-new')) return openGoal();
    const edit = event.target.closest('[data-goal-edit]');
    if (edit) return openGoal(state.goals.find((g) => g.id === Number(edit.dataset.goalEdit)));
    const deposit = event.target.closest('[data-goal-deposit]');
    if (deposit) {
      const id = Number(deposit.dataset.goalDeposit);
      const before = state.goals.find((g) => g.id === id);
      const { data } = await api(`/goals/${id}/deposit`, { method: 'POST', body: { amount: Number(deposit.dataset.amount) } });
      state.goals = state.goals.map((g) => (g.id === id ? data : g));
      renderGoals();
      renderOverview();
      if (data.progress >= 100 && before.progress < 100) celebrate();
    }
  });
  $('#goal-color-picker').addEventListener('click', (event) => {
    const option = event.target.closest('[data-color]');
    if (!option) return;
    goalColor = option.dataset.color;
    $$('.color', $('#goal-color-picker')).forEach((el) => el.classList.toggle('is-active', el === option));
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const body = {
      name: form.name.value.trim(),
      target: parseAmount(form.target.value) || form.target.value,
      saved: form.saved.value.trim() ? parseAmount(form.saved.value) : 0,
      deadline: form.deadline.value || null,
      color: goalColor,
    };
    try {
      if (editingGoal) await api(`/goals/${editingGoal.id}`, { method: 'PATCH', body });
      else await api('/goals', { method: 'POST', body });
      dialog.close();
      toast(t('toast.saved'));
      state.goals = (await api('/goals')).data;
      renderGoals();
      renderOverview();
    } catch (error) {
      showErrors(form, error);
    }
  });

  $('#goal-delete').addEventListener('click', async () => {
    dialog.close();
    if (!await confirmDialog(editingGoal.name)) return;
    await api(`/goals/${editingGoal.id}`, { method: 'DELETE' });
    toast(t('toast.deleted'));
    state.goals = state.goals.filter((g) => g.id !== editingGoal.id);
    renderGoals();
    renderOverview();
  });
}

/** A short burst of coins when a goal is reached. */
function celebrate() {
  if (calm()) return;
  const layer = document.createElement('div');
  layer.className = 'coin-rain';
  for (let i = 0; i < 28; i++) {
    const coin = document.createElement('span');
    coin.style.setProperty('--x', `${Math.random() * 100}vw`);
    coin.style.setProperty('--d', `${0.9 + Math.random() * 0.9}s`);
    coin.style.setProperty('--delay', `${Math.random() * 0.4}s`);
    coin.style.setProperty('--r', `${Math.random() * 720 - 360}deg`);
    layer.appendChild(coin);
  }
  document.body.appendChild(layer);
  setTimeout(() => layer.remove(), 2600);
}

/* ---------- settings ---------- */

function initSettings() {
  $('#profile-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const { data } = await api('/me', { method: 'PATCH', body: { name: form.name.value.trim(), currency: form.currency.value } });
    state.user = data;
    $('#user-name').textContent = data.name;
    $('#avatar').textContent = data.name.charAt(0).toUpperCase();
    $('#tx-currency').textContent = fmt(0).replace(/[\d.,\s−-]/g, '') || '€';
    toast(t('toast.saved'));
    renderAll();
  });

  $$('#setting-theme button').forEach((button) => button.addEventListener('click', (event) => {
    applyTheme(button.dataset.value, true, { x: event.clientX, y: event.clientY });
    savePrefs();
  }));
  $$('#setting-lang button').forEach((button) => button.addEventListener('click', () => { applyLanguage(button.dataset.value); savePrefs(); }));
  $$('#setting-motion button').forEach((button) => button.addEventListener('click', () => { applyMotion(button.dataset.value); savePrefs(); }));

  $('#export-all').addEventListener('click', () => download('/transactions/export', {}, 'centwise-all.csv'));
  $('#logout').addEventListener('click', logout);
  $('#delete-account').addEventListener('click', async () => {
    if (!await confirmDialog(t('settings.deleteConfirm'))) return;
    await api('/me', { method: 'DELETE' });
    setToken(null);
    showAuth();
  });
  $('#demo-register').addEventListener('click', async () => {
    await logout();
    $('[data-auth-tab="register"]').click();
  });
}

/* ---------- navigation ---------- */

function route() {
  const view = VIEWS.includes(location.hash.slice(1)) ? location.hash.slice(1) : 'overview';
  const changed = view !== state.view;
  state.view = view;
  $$('[data-view-panel]').forEach((panel) => {
    panel.hidden = panel.dataset.viewPanel !== view;
    if (!panel.hidden && changed) {
      panel.classList.remove('is-entering');
      void panel.offsetWidth;
      panel.classList.add('is-entering');
    }
  });
  $$('[data-view]').forEach((link) => link.classList.toggle('is-active', link.dataset.view === view));
  const active = $(`.nav [data-view="${view}"]`);
  const thumb = $('.nav-thumb');
  if (active && thumb) {
    thumb.style.height = `${active.offsetHeight}px`;
    thumb.style.transform = `translateY(${active.offsetTop}px)`;
  }
  $('.month-switch').classList.toggle('is-hidden', view === 'goals' || view === 'settings');
  if (!state.user) return;
  if (view === 'transactions') loadTransactions(true);
  if (view === 'overview' && state.summary) requestAnimationFrame(renderOverview);
  if (view === 'settings') requestAnimationFrame(() => {
    setSegment($('#setting-theme'), state.prefs.theme);
    setSegment($('#setting-lang'), state.prefs.lang);
    setSegment($('#setting-motion'), state.prefs.motion);
  });
  if (view === 'transactions') requestAnimationFrame(() => setSegment($('#tx-type'), state.tx.type, 'type'));
  scrollTo({ top: 0, behavior: calm() ? 'auto' : 'smooth' });
}

function initShell() {
  addEventListener('hashchange', route);

  $$('[data-month]').forEach((button) => button.addEventListener('click', async () => {
    const next = shiftMonth(state.month, Number(button.dataset.month));
    if (next > monthKey()) return;
    state.month = next;
    $('#month-label').classList.remove('flip');
    void $('#month-label').offsetWidth;
    $('#month-label').classList.add('flip');
    await loadSummary();
    renderAll();
    if (state.view === 'transactions') loadTransactions(true);
  }));

  $$('[data-add-transaction]').forEach((button) => button.addEventListener('click', () => openTransaction()));

  $('#theme-toggle').addEventListener('click', (event) => {
    const box = event.currentTarget.getBoundingClientRect();
    applyTheme(state.prefs.theme === 'dark' ? 'light' : 'dark', true, { x: box.left + box.width / 2, y: box.top + box.height / 2 });
    savePrefs();
  });
  $$('.lang-toggle button').forEach((button) => button.addEventListener('click', () => { applyLanguage(button.dataset.lang); savePrefs(); }));

  $$('dialog').forEach((dialog) => {
    $$('[data-close]', dialog).forEach((button) => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
  });

  document.addEventListener('keydown', (event) => {
    if (event.target.closest('input, textarea, select, dialog') || event.metaKey || event.ctrlKey || event.altKey) return;
    if (state.user && event.key.toLowerCase() === 'n') {
      event.preventDefault();
      openTransaction();
    }
  });

  let resizeTimer;
  addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      if (state.user && state.summary && state.view === 'overview') renderOverview();
      route();
    }, 200);
  });
}

/* ---------- start ---------- */

async function start() {
  loadPrefs();
  fillIcons();
  applyMotion(state.prefs.motion);
  applyTheme(state.prefs.theme);
  setLanguage(state.prefs.lang);

  initShell();
  initAuth();
  initTransactions();
  initTransactionDialog();
  initCategoryDialog();
  initGoals();
  initSettings();
  applyLanguage(state.prefs.lang);

  if (!getToken()) {
    showAuth();
    return;
  }
  try {
    const { data } = await api('/me');
    await enterApp(data);
  } catch {
    showAuth();
  }
}

start();
