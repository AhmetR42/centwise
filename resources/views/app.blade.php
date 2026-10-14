<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Centwise</title>
    <meta name="description" content="Centwise is a personal budget app: track income and spending, set monthly budgets and save towards goals.">
    <meta name="theme-color" content="#f1f1ef">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css?v={{ filemtime(public_path('css/app.css')) }}">
    <script>
        // Apply the saved theme before the first paint to avoid a flash.
        try {
            const saved = JSON.parse(localStorage.getItem('centwise:prefs') || '{}');
            document.documentElement.dataset.theme = saved.theme || 'light';
            if (saved.motion === 'reduced') document.documentElement.dataset.motion = 'reduced';
        } catch (e) {}
    </script>
    <script type="module" src="/js/app.js?v={{ filemtime(public_path('js/app.js')) }}"></script>
</head>
<body>
<div class="backdrop" aria-hidden="true"><span></span><span></span><span></span></div>

{{-- Sign in / register --}}
<main id="auth" class="auth" hidden>
    <header class="site-head">
        <span class="site-head-note" data-i18n="auth.kicker"></span>
        <a class="brand brand-center" href="/"><span>Cent</span>@include('partials.coin')<span>wise</span></a>
        <div class="lang-toggle" role="group" aria-label="Language">
            <span class="lang-thumb" aria-hidden="true"></span>
            <button type="button" data-lang="en">EN</button>
            <button type="button" data-lang="nl">NL</button>
        </div>
    </header>

    <section class="hero">
        <div class="hero-text">
            <p class="kicker" data-i18n="auth.kicker"></p>
            <h1 class="hero-title"><span data-i18n="auth.titleStart"></span><br><span class="typed" id="typed"></span></h1>
            <p class="hero-pitch" data-i18n="auth.pitch"></p>
            <div class="hero-actions">
                <button type="button" class="btn btn-primary btn-big" id="demo-button"><span data-i18n="auth.demo"></span></button>
                <a href="#get-started" class="btn btn-outline btn-big" data-i18n="auth.signIn"></a>
            </div>
            <p class="hint" data-i18n="auth.demoHint"></p>
        </div>
        @include('partials.illustration')
    </section>
    <svg class="wave wave-light" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true"><path d="M0 70C220 10 420 10 640 50s420 70 800-10v80H0Z"/></svg>

    <section class="steps-section">
        <h2 class="rule-title" data-i18n="auth.stepsTitle"></h2>
        <ol class="steps">
            <li><span class="step-num">1</span><div><h3 data-i18n="auth.step1"></h3><p data-i18n="auth.step1Text"></p></div></li>
            <li><span class="step-num">2</span><div><h3 data-i18n="auth.step2"></h3><p data-i18n="auth.step2Text"></p></div></li>
            <li><span class="step-num">3</span><div><h3 data-i18n="auth.step3"></h3><p data-i18n="auth.step3Text"></p></div></li>
        </ol>
        <ul class="features">
            <li><span class="feature-icon" data-icon="gauge"></span><b data-i18n="auth.point1Title"></b><span data-i18n="auth.point1"></span></li>
            <li><span class="feature-icon" data-icon="overview"></span><b data-i18n="auth.point2Title"></b><span data-i18n="auth.point2"></span></li>
            <li><span class="feature-icon" data-icon="target"></span><b data-i18n="auth.point3Title"></b><span data-i18n="auth.point3"></span></li>
        </ul>
    </section>

    <section class="cta" id="get-started">
        <svg class="wave wave-yellow" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true"><path d="M0 40c240 0 360 80 620 70s420-90 820-40v50H0Z"/></svg>
        <div class="cta-inner">
            <div class="cta-text">
                <h2 data-i18n="auth.ctaTitle"></h2>
                <p data-i18n="auth.ctaText"></p>
                <span class="cta-blob" aria-hidden="true"></span>
            </div>
            <div class="auth-card">
                <div class="tabs" role="tablist">
                    <button type="button" role="tab" class="tab is-active" data-auth-tab="login" data-i18n="auth.signIn"></button>
                    <button type="button" role="tab" class="tab" data-auth-tab="register" data-i18n="auth.register"></button>
                    <span class="tab-thumb" aria-hidden="true"></span>
                </div>
                <form id="login-form" class="form" novalidate>
                    <label class="field"><span data-i18n="auth.email"></span><input name="email" type="email" autocomplete="email" required></label>
                    <label class="field"><span data-i18n="auth.password"></span><input name="password" type="password" autocomplete="current-password" required></label>
                    <p class="form-error" data-error></p>
                    <button class="btn btn-dark btn-wide" data-i18n="auth.signIn"></button>
                </form>
                <form id="register-form" class="form" novalidate hidden>
                    <label class="field"><span data-i18n="auth.name"></span><input name="name" autocomplete="name" required></label>
                    <label class="field"><span data-i18n="auth.email"></span><input name="email" type="email" autocomplete="email" required></label>
                    <div class="field-row">
                        <label class="field"><span data-i18n="auth.password"></span><input name="password" type="password" autocomplete="new-password" minlength="8" required></label>
                        <label class="field"><span data-i18n="auth.passwordConfirm"></span><input name="password_confirmation" type="password" autocomplete="new-password" required></label>
                    </div>
                    <p class="form-error" data-error></p>
                    <button class="btn btn-dark btn-wide" data-i18n="auth.register"></button>
                </form>
            </div>
        </div>
    </section>

    <footer class="site-foot">
        <svg class="wave wave-dark" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true"><path d="M0 50c300-60 520 40 760 40s420-70 680-40v70H0Z"/></svg>
        <div class="site-foot-inner">
            <span class="brand brand-light"><span>Cent</span>@include('partials.coin')<span>wise</span></span>
            <div><h4 data-i18n="auth.footBuilt"></h4><p>Laravel 13 · Sanctum · SQLite<br>JavaScript · SVG</p></div>
            <div><h4>API</h4><p>/api/v1/transactions<br>/api/v1/stats/summary</p></div>
            <div><h4>GitHub</h4><p><a href="https://github.com/AhmetR42/centwise">AhmetR42/centwise</a></p></div>
        </div>
    </footer>
</main>

{{-- The app itself --}}
<div id="app" class="shell" hidden>
    <aside class="sidebar">
        <a class="brand brand-light" href="#overview"><span>Cent</span>@include('partials.coin')<span>wise</span></a>
        <nav class="nav" aria-label="Main">
            <a href="#overview" data-view="overview"><span data-icon="overview"></span><span data-i18n="nav.overview"></span></a>
            <a href="#transactions" data-view="transactions"><span data-icon="list"></span><span data-i18n="nav.transactions"></span></a>
            <a href="#budgets" data-view="budgets"><span data-icon="gauge"></span><span data-i18n="nav.budgets"></span></a>
            <a href="#goals" data-view="goals"><span data-icon="target"></span><span data-i18n="nav.goals"></span></a>
            <a href="#settings" data-view="settings"><span data-icon="cog"></span><span data-i18n="nav.settings"></span></a>
            <span class="nav-thumb" aria-hidden="true"></span>
        </nav>
        <div class="sidebar-foot">
            <div class="user-chip"><span class="avatar" id="avatar"></span><span id="user-name"></span></div>
            <p class="shortcut-hint" data-i18n="shortcut"></p>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div class="month-switch">
                <button type="button" class="icon-btn" data-month="-1" data-i18n-title="month.previous"><span data-icon="left"></span></button>
                <h2 id="month-label" class="month-label"></h2>
                <button type="button" class="icon-btn" data-month="1" data-i18n-title="month.next"><span data-icon="right"></span></button>
            </div>
            <div class="top-actions">
                <button type="button" class="theme-toggle" id="theme-toggle" role="switch" aria-label="Dark mode">
                    <span class="theme-track"><span class="theme-thumb"><span data-icon="sun" class="sun"></span><span data-icon="moon" class="moon"></span></span></span>
                </button>
                <div class="lang-toggle" role="group" aria-label="Language">
                    <span class="lang-thumb" aria-hidden="true"></span>
                    <button type="button" data-lang="en">EN</button>
                    <button type="button" data-lang="nl">NL</button>
                </div>
                <button type="button" class="btn btn-primary add-btn" data-add-transaction>
                    <span data-icon="plus"></span><span data-i18n="add"></span>
                </button>
            </div>
        </header>

        <div class="demo-banner" id="demo-banner" hidden>
            <span data-i18n="demoBanner"></span>
            <button type="button" class="link-btn" id="demo-register" data-i18n="demoBannerAction"></button>
        </div>

        {{-- Overview --}}
        <section class="view" id="view-overview" data-view-panel="overview">
            <div class="stats">
                <article class="stat stat-hero">
                    <span class="stat-label" data-i18n="overview.balance"></span>
                    <strong class="stat-value" id="stat-balance">€0.00</strong>
                    <span class="stat-sub" id="stat-count"></span>
                    <svg class="stat-wave" viewBox="0 0 400 60" preserveAspectRatio="none" aria-hidden="true"><path d="M0 30c60-28 120-28 200 0s140 28 200 0v30H0Z"/></svg>
                    <span class="stat-blob" aria-hidden="true"></span>
                </article>
                <article class="stat">
                    <span class="stat-icon stat-icon-income" data-icon="up"></span>
                    <span class="stat-label" data-i18n="overview.income"></span>
                    <strong class="stat-value" id="stat-income">€0.00</strong>
                    <span class="stat-sub" id="stat-income-change"></span>
                </article>
                <article class="stat">
                    <span class="stat-icon stat-icon-expense" data-icon="down"></span>
                    <span class="stat-label" data-i18n="overview.expense"></span>
                    <strong class="stat-value" id="stat-expense">€0.00</strong>
                    <span class="stat-sub" id="stat-expense-change"></span>
                </article>
                <article class="stat">
                    <span class="stat-icon" data-icon="coin"></span>
                    <span class="stat-label" data-i18n="overview.savingsRate"></span>
                    <strong class="stat-value" id="stat-rate">0%</strong>
                    <span class="meter"><span id="stat-rate-bar"></span></span>
                </article>
            </div>

            <div class="grid">
                <article class="panel span-2">
                    <header class="panel-head">
                        <h3 data-i18n="overview.pace"></h3>
                        <p class="panel-hint" data-i18n="overview.paceHint"></p>
                    </header>
                    <div class="chart-box" id="pace-chart"></div>
                    <p class="pace-note" id="pace-note"></p>
                </article>
                <article class="panel">
                    <header class="panel-head"><h3 data-i18n="overview.whereTo"></h3></header>
                    <div class="donut-box" id="donut"></div>
                    <ul class="legend" id="donut-legend"></ul>
                </article>

                <article class="panel">
                    <header class="panel-head">
                        <h3 data-i18n="overview.budgets"></h3>
                        <a href="#budgets" class="link-btn" data-i18n="overview.seeAll"></a>
                    </header>
                    <ul class="budget-mini" id="budget-mini"></ul>
                </article>
                <article class="panel span-2">
                    <header class="panel-head">
                        <h3 data-i18n="overview.trend"></h3>
                        <div class="chart-key"><span class="swatch swatch-income"></span><span data-i18n="overview.income"></span><span class="swatch swatch-expense"></span><span data-i18n="overview.expense"></span></div>
                    </header>
                    <div class="chart-box" id="trend-chart"></div>
                </article>

                <article class="panel span-2">
                    <header class="panel-head">
                        <h3 data-i18n="overview.recent"></h3>
                        <a href="#transactions" class="link-btn" data-i18n="overview.seeAll"></a>
                    </header>
                    <ul class="tx-list" id="recent-list"></ul>
                </article>
                <article class="panel">
                    <header class="panel-head">
                        <h3 data-i18n="overview.goals"></h3>
                        <a href="#goals" class="link-btn" data-i18n="overview.seeAll"></a>
                    </header>
                    <ul class="goal-mini" id="goal-mini"></ul>
                </article>
            </div>
        </section>

        {{-- Transactions --}}
        <section class="view" id="view-transactions" data-view-panel="transactions" hidden>
            <div class="filters panel">
                <label class="search">
                    <span data-icon="search"></span>
                    <input type="search" id="tx-search" data-i18n-placeholder="tx.search">
                </label>
                <div class="segmented underline" id="tx-type" role="group">
                    <button type="button" data-type="" class="is-active" data-i18n="tx.all"></button>
                    <button type="button" data-type="expense" data-i18n="tx.expense"></button>
                    <button type="button" data-type="income" data-i18n="tx.income"></button>
                    <span class="segmented-thumb" aria-hidden="true"></span>
                </div>
                <select id="tx-category" class="select"></select>
                <select id="tx-sort" class="select">
                    <option value="newest" data-i18n="tx.sort.newest"></option>
                    <option value="oldest" data-i18n="tx.sort.oldest"></option>
                    <option value="highest" data-i18n="tx.sort.highest"></option>
                    <option value="lowest" data-i18n="tx.sort.lowest"></option>
                </select>
                <label class="check"><input type="checkbox" id="tx-all-time"><span data-i18n="tx.allTime"></span></label>
                <button type="button" class="btn btn-ghost" id="tx-export"><span data-icon="download"></span><span data-i18n="tx.export"></span></button>
            </div>
            <div class="tx-summary" id="tx-summary"></div>
            <div id="tx-days" class="tx-days"></div>
            <div class="empty" id="tx-empty" hidden>
                <span class="empty-art" data-icon="coin"></span>
                <p data-i18n="tx.empty"></p>
                <button type="button" class="btn btn-primary" data-add-transaction data-i18n="tx.emptyAction"></button>
            </div>
            <button type="button" class="btn btn-ghost btn-wide" id="tx-more" hidden data-i18n="tx.loadMore"></button>
        </section>

        {{-- Budgets and categories --}}
        <section class="view" id="view-budgets" data-view-panel="budgets" hidden>
            <div class="page-head">
                <div>
                    <h2 class="rule-title" data-i18n="budgets.title"></h2>
                    <p class="muted" data-i18n="budgets.intro"></p>
                </div>
                <button type="button" class="btn btn-primary" id="add-category"><span data-icon="plus"></span><span data-i18n="budgets.addCategory"></span></button>
            </div>
            <div class="budget-total panel" id="budget-total"></div>
            <ul class="budget-list" id="budget-list"></ul>
            <h3 class="section-title" data-i18n="budgets.incomeCategories"></h3>
            <ul class="chip-list" id="income-categories"></ul>
        </section>

        {{-- Goals --}}
        <section class="view" id="view-goals" data-view-panel="goals" hidden>
            <div class="page-head">
                <div>
                    <h2 class="rule-title" data-i18n="goals.title"></h2>
                    <p class="muted" data-i18n="goals.intro"></p>
                </div>
                <button type="button" class="btn btn-primary" id="add-goal"><span data-icon="plus"></span><span data-i18n="goals.add"></span></button>
            </div>
            <div class="goal-grid" id="goal-grid"></div>
        </section>

        {{-- Settings --}}
        <section class="view" id="view-settings" data-view-panel="settings" hidden>
            <div class="settings-grid">
                <form class="panel form" id="profile-form">
                    <h3 data-i18n="settings.profile"></h3>
                    <label class="field"><span data-i18n="settings.name"></span><input name="name" required maxlength="60"></label>
                    <label class="field"><span data-i18n="settings.currency"></span>
                        <select name="currency" class="select">
                            <option value="EUR">EUR · €</option>
                            <option value="USD">USD · $</option>
                            <option value="GBP">GBP · £</option>
                            <option value="TRY">TRY · ₺</option>
                        </select>
                    </label>
                    <button class="btn btn-primary" data-i18n="save"></button>
                </form>
                <div class="panel form">
                    <h3 data-i18n="settings.appearance"></h3>
                    <div class="setting-row"><span data-i18n="settings.theme"></span>
                        <div class="segmented" id="setting-theme">
                            <button type="button" data-value="dark" data-i18n="theme.dark"></button>
                            <button type="button" data-value="light" data-i18n="theme.light"></button>
                            <span class="segmented-thumb" aria-hidden="true"></span>
                        </div>
                    </div>
                    <div class="setting-row"><span data-i18n="settings.language"></span>
                        <div class="segmented" id="setting-lang">
                            <button type="button" data-value="en">English</button>
                            <button type="button" data-value="nl">Nederlands</button>
                            <span class="segmented-thumb" aria-hidden="true"></span>
                        </div>
                    </div>
                    <div class="setting-row"><span data-i18n="settings.motion"></span>
                        <div class="segmented" id="setting-motion">
                            <button type="button" data-value="full" data-i18n="settings.motionOn"></button>
                            <button type="button" data-value="reduced" data-i18n="settings.motionOff"></button>
                            <span class="segmented-thumb" aria-hidden="true"></span>
                        </div>
                    </div>
                </div>
                <div class="panel form">
                    <h3 data-i18n="settings.data"></h3>
                    <button type="button" class="btn btn-ghost" id="export-all"><span data-icon="download"></span><span data-i18n="settings.exportAll"></span></button>
                    <button type="button" class="btn btn-ghost" id="logout"><span data-icon="logout"></span><span data-i18n="settings.logout"></span></button>
                    <button type="button" class="btn btn-danger" id="delete-account"><span data-icon="trash"></span><span data-i18n="settings.delete"></span></button>
                </div>
                <div class="panel">
                    <h3 data-i18n="settings.api"></h3>
                    <p class="muted" data-i18n="settings.apiText"></p>
                    <pre class="code">GET  /api/v1/stats/summary?month=2026-10
POST /api/v1/transactions
Authorization: Bearer &lt;token&gt;</pre>
                </div>
            </div>
        </section>
    </div>

    <nav class="tabbar" aria-label="Main">
        <a href="#overview" data-view="overview"><span data-icon="overview"></span><span data-i18n="nav.overview"></span></a>
        <a href="#transactions" data-view="transactions"><span data-icon="list"></span><span data-i18n="nav.transactions"></span></a>
        <button type="button" class="tabbar-add" data-add-transaction data-i18n-title="addTransaction"><span data-icon="plus"></span></button>
        <a href="#budgets" data-view="budgets"><span data-icon="gauge"></span><span data-i18n="nav.budgets"></span></a>
        <a href="#goals" data-view="goals"><span data-icon="target"></span><span data-i18n="nav.goals"></span></a>
    </nav>
</div>

{{-- Transaction dialog --}}
<dialog class="sheet" id="tx-dialog">
    <form method="dialog" class="form" id="tx-form" novalidate>
        <header class="sheet-head">
            <h3 id="tx-dialog-title"></h3>
            <button type="button" class="icon-btn" data-close data-i18n-title="close"><span data-icon="close"></span></button>
        </header>
        <div class="segmented segmented-big" id="tx-form-type">
            <button type="button" data-value="expense" data-i18n="tx.expense"></button>
            <button type="button" data-value="income" data-i18n="tx.income"></button>
            <span class="segmented-thumb" aria-hidden="true"></span>
        </div>
        <label class="amount-field">
            <span class="currency-sign" id="tx-currency">€</span>
            <input name="amount" inputmode="decimal" autocomplete="off" placeholder="0.00" required>
        </label>
        <p class="form-error" data-error-for="amount"></p>
        <fieldset class="field">
            <legend data-i18n="form.category"></legend>
            <div class="category-picker" id="tx-categories"></div>
            <p class="form-error" data-error-for="category_id"></p>
        </fieldset>
        <div class="field-row">
            <label class="field"><span data-i18n="form.date"></span><input name="occurred_on" type="date" required></label>
            <label class="field"><span data-i18n="form.description"></span><input name="description" maxlength="120" data-i18n-placeholder="form.descriptionPlaceholder"></label>
        </div>
        <footer class="sheet-foot">
            <button type="button" class="btn btn-danger-ghost" id="tx-delete" hidden><span data-icon="trash"></span><span data-i18n="delete"></span></button>
            <span class="spacer"></span>
            <button type="button" class="btn btn-ghost" data-close data-i18n="cancel"></button>
            <button class="btn btn-primary" data-i18n="save"></button>
        </footer>
    </form>
</dialog>

{{-- Category dialog --}}
<dialog class="sheet" id="category-dialog">
    <form method="dialog" class="form" id="category-form" novalidate>
        <header class="sheet-head">
            <h3 id="category-dialog-title"></h3>
            <button type="button" class="icon-btn" data-close data-i18n-title="close"><span data-icon="close"></span></button>
        </header>
        <div class="segmented" id="category-form-type">
            <button type="button" data-value="expense" data-i18n="tx.expense"></button>
            <button type="button" data-value="income" data-i18n="tx.income"></button>
            <span class="segmented-thumb" aria-hidden="true"></span>
        </div>
        <label class="field"><span data-i18n="form.name"></span><input name="name" maxlength="60" required></label>
        <p class="form-error" data-error-for="name"></p>
        <fieldset class="field"><legend data-i18n="form.icon"></legend><div class="icon-picker" id="icon-picker"></div></fieldset>
        <fieldset class="field"><legend data-i18n="form.color"></legend><div class="color-picker" id="color-picker"></div></fieldset>
        <label class="field" id="budget-field"><span><span data-i18n="form.budget"></span> <small class="muted" data-i18n="form.budgetHint"></small></span><input name="budget" inputmode="decimal" autocomplete="off"></label>
        <p class="form-error" data-error-for="budget"></p>
        <footer class="sheet-foot">
            <button type="button" class="btn btn-danger-ghost" id="category-delete" hidden><span data-icon="trash"></span><span data-i18n="delete"></span></button>
            <span class="spacer"></span>
            <button type="button" class="btn btn-ghost" data-close data-i18n="cancel"></button>
            <button class="btn btn-primary" data-i18n="save"></button>
        </footer>
    </form>
</dialog>

{{-- Goal dialog --}}
<dialog class="sheet" id="goal-dialog">
    <form method="dialog" class="form" id="goal-form" novalidate>
        <header class="sheet-head">
            <h3 id="goal-dialog-title"></h3>
            <button type="button" class="icon-btn" data-close data-i18n-title="close"><span data-icon="close"></span></button>
        </header>
        <label class="field"><span data-i18n="form.name"></span><input name="name" maxlength="60" required></label>
        <p class="form-error" data-error-for="name"></p>
        <div class="field-row">
            <label class="field"><span data-i18n="form.target"></span><input name="target" inputmode="decimal" required></label>
            <label class="field"><span data-i18n="form.saved"></span><input name="saved" inputmode="decimal"></label>
        </div>
        <p class="form-error" data-error-for="target"></p>
        <label class="field"><span><span data-i18n="form.deadline"></span> <small class="muted" data-i18n="form.optional"></small></span><input name="deadline" type="date"></label>
        <p class="form-error" data-error-for="deadline"></p>
        <fieldset class="field"><legend data-i18n="form.color"></legend><div class="color-picker" id="goal-color-picker"></div></fieldset>
        <footer class="sheet-foot">
            <button type="button" class="btn btn-danger-ghost" id="goal-delete" hidden><span data-icon="trash"></span><span data-i18n="delete"></span></button>
            <span class="spacer"></span>
            <button type="button" class="btn btn-ghost" data-close data-i18n="cancel"></button>
            <button class="btn btn-primary" data-i18n="save"></button>
        </footer>
    </form>
</dialog>

{{-- Confirm dialog --}}
<dialog class="sheet sheet-small" id="confirm-dialog">
    <form method="dialog" class="form">
        <h3 data-i18n="confirm"></h3>
        <p id="confirm-text" class="muted"></p>
        <footer class="sheet-foot">
            <span class="spacer"></span>
            <button value="cancel" class="btn btn-ghost" data-i18n="cancel"></button>
            <button value="ok" class="btn btn-danger" data-i18n="delete"></button>
        </footer>
    </form>
</dialog>

<div class="toasts" id="toasts" aria-live="polite"></div>
</body>
</html>
