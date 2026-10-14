// Hand-made SVG charts. Colours come from CSS variables so they follow the theme.

const NS = 'http://www.w3.org/2000/svg';

function svg(width, height) {
  const el = document.createElementNS(NS, 'svg');
  el.setAttribute('viewBox', `0 0 ${width} ${height}`);
  el.setAttribute('class', 'chart');
  el.setAttribute('role', 'img');
  return el;
}

function node(name, attrs = {}, parent) {
  const el = document.createElementNS(NS, name);
  for (const [key, value] of Object.entries(attrs)) el.setAttribute(key, value);
  if (parent) parent.appendChild(el);
  return el;
}

function niceMax(value) {
  if (value <= 0) return 10;
  const magnitude = 10 ** Math.floor(Math.log10(value));
  for (const step of [1, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10]) {
    if (step * magnitude >= value) return step * magnitude;
  }
  return 10 * magnitude;
}

function tooltip(container) {
  let tip = container.querySelector('.chart-tip');
  if (!tip) {
    tip = document.createElement('div');
    tip.className = 'chart-tip';
    container.appendChild(tip);
  }
  return {
    show(html, x, y) {
      tip.innerHTML = html;
      tip.style.left = `${x}px`;
      tip.style.top = `${y}px`;
      tip.classList.add('is-visible');
    },
    hide() { tip.classList.remove('is-visible'); },
  };
}

/**
 * Running total of spending through the month, with the budget pace as a dashed line.
 * points: [{date, spent, pace}]
 */
export function paceChart(container, points, { format, label }) {
  container.innerHTML = '';
  const width = Math.max(320, container.clientWidth || 640);
  const height = 280;
  const pad = { top: 16, right: 14, bottom: 26, left: 52 };
  const chart = svg(width, height);
  const innerW = width - pad.left - pad.right;
  const innerH = height - pad.top - pad.bottom;

  const max = niceMax(Math.max(...points.map((p) => Math.max(p.spent, p.pace ?? 0)), 1));
  const x = (i) => pad.left + (points.length === 1 ? innerW / 2 : (i / (points.length - 1)) * innerW);
  const y = (v) => pad.top + innerH - (v / max) * innerH;

  for (let i = 0; i <= 4; i++) {
    const value = (max / 4) * i;
    node('line', { x1: pad.left, x2: width - pad.right, y1: y(value), y2: y(value), class: 'grid' }, chart);
    const text = node('text', { x: pad.left - 8, y: y(value) + 4, class: 'axis', 'text-anchor': 'end' }, chart);
    text.textContent = format(value, true);
  }

  points.forEach((point, i) => {
    if (i % 7 === 0 || i === points.length - 1) {
      const text = node('text', { x: x(i), y: height - 6, class: 'axis', 'text-anchor': 'middle' }, chart);
      text.textContent = Number(point.date.slice(8));
    }
  });

  // Only draw up to today for the current month
  const today = new Date().toISOString().slice(0, 10);
  const shown = points.filter((p) => p.date <= today);
  const visible = shown.length ? shown : points;

  const defs = node('defs', {}, chart);
  const gradient = node('linearGradient', { id: 'pace-fill', x1: 0, x2: 0, y1: 0, y2: 1 }, defs);
  node('stop', { offset: '0%', 'stop-color': 'var(--accent)', 'stop-opacity': 0.35 }, gradient);
  node('stop', { offset: '100%', 'stop-color': 'var(--accent)', 'stop-opacity': 0 }, gradient);

  if (points[0].pace !== null) {
    const pace = points.map((p, i) => `${i ? 'L' : 'M'}${x(i)},${y(p.pace)}`).join('');
    node('path', { d: pace, class: 'pace-line' }, chart);
  }

  const line = visible.map((p, i) => `${i ? 'L' : 'M'}${x(i)},${y(p.spent)}`).join('');
  const area = `${line}L${x(visible.length - 1)},${y(0)}L${x(0)},${y(0)}Z`;
  node('path', { d: area, class: 'spent-area', fill: 'url(#pace-fill)' }, chart);
  const path = node('path', { d: line, class: 'spent-line' }, chart);

  const last = visible[visible.length - 1];
  node('circle', { cx: x(visible.length - 1), cy: y(last.spent), r: 5, class: 'spent-dot' }, chart);

  const guide = node('line', { y1: pad.top, y2: pad.top + innerH, class: 'guide' }, chart);
  const marker = node('circle', { r: 4.5, class: 'spent-marker' }, chart);
  const tip = tooltip(container);

  chart.addEventListener('pointermove', (event) => {
    const box = chart.getBoundingClientRect();
    const px = ((event.clientX - box.left) / box.width) * width;
    const index = Math.max(0, Math.min(visible.length - 1, Math.round(((px - pad.left) / innerW) * (points.length - 1))));
    const point = visible[index];
    guide.setAttribute('x1', x(index));
    guide.setAttribute('x2', x(index));
    marker.setAttribute('cx', x(index));
    marker.setAttribute('cy', y(point.spent));
    chart.classList.add('is-hovering');
    tip.show(label(point), (x(index) / width) * box.width, (y(point.spent) / height) * box.height);
  });
  chart.addEventListener('pointerleave', () => {
    chart.classList.remove('is-hovering');
    tip.hide();
  });

  container.prepend(chart);
  const length = path.getTotalLength?.() ?? 0;
  path.style.setProperty('--length', length);
}

/**
 * Ring chart of spending per category.
 * slices: [{name, color, value}]
 */
export function donut(container, slices, { center, onSelect } = {}) {
  container.innerHTML = '';
  const size = 220;
  const radius = 84;
  const stroke = 26;
  const chart = svg(size, size);
  chart.classList.add('donut');
  const total = slices.reduce((sum, s) => sum + s.value, 0);
  const circumference = 2 * Math.PI * radius;

  node('circle', { cx: size / 2, cy: size / 2, r: radius, class: 'donut-track', 'stroke-width': stroke }, chart);

  let offset = 0;
  slices.forEach((slice, index) => {
    const length = total ? (slice.value / total) * circumference : 0;
    const gap = slices.length > 1 ? Math.min(3, length / 3) : 0;
    const arc = node('circle', {
      cx: size / 2,
      cy: size / 2,
      r: radius,
      class: 'donut-slice',
      stroke: slice.color,
      'stroke-width': stroke,
      'stroke-dasharray': `${Math.max(0, length - gap)} ${circumference}`,
      'stroke-dashoffset': -offset,
      transform: `rotate(-90 ${size / 2} ${size / 2})`,
    }, chart);
    arc.style.setProperty('--delay', `${index * 60}ms`);
    arc.style.setProperty('--circumference', circumference);
    arc.addEventListener('pointerenter', () => onSelect?.(slice));
    arc.addEventListener('pointerleave', () => onSelect?.(null));
    offset += length;
  });

  const label = document.createElement('div');
  label.className = 'donut-center';
  label.innerHTML = center ?? '';

  container.append(chart, label);
  return label;
}

/**
 * Income and spending per month as paired bars.
 * months: [{label, income, expense}]
 */
export function trendBars(container, months, { format, label }) {
  container.innerHTML = '';
  const width = Math.max(300, container.clientWidth || 480);
  const height = 220;
  const pad = { top: 14, right: 8, bottom: 26, left: 48 };
  const chart = svg(width, height);
  const innerW = width - pad.left - pad.right;
  const innerH = height - pad.top - pad.bottom;
  const max = niceMax(Math.max(...months.flatMap((m) => [m.income, m.expense]), 1));
  const y = (v) => pad.top + innerH - (v / max) * innerH;
  const slot = innerW / months.length;
  const bar = Math.min(18, slot / 3.2);

  for (let i = 0; i <= 3; i++) {
    const value = (max / 3) * i;
    node('line', { x1: pad.left, x2: width - pad.right, y1: y(value), y2: y(value), class: 'grid' }, chart);
    const text = node('text', { x: pad.left - 8, y: y(value) + 4, class: 'axis', 'text-anchor': 'end' }, chart);
    text.textContent = format(value, true);
  }

  const tip = tooltip(container);
  months.forEach((month, index) => {
    const cx = pad.left + slot * index + slot / 2;
    const group = node('g', { class: 'bar-group' }, chart);
    group.style.setProperty('--delay', `${index * 70}ms`);
    node('rect', { x: cx - bar - 2, y: y(month.income), width: bar, height: Math.max(0, y(0) - y(month.income)), rx: 4, class: 'col col-income' }, group);
    node('rect', { x: cx + 2, y: y(month.expense), width: bar, height: Math.max(0, y(0) - y(month.expense)), rx: 4, class: 'col col-expense' }, group);
    node('rect', { x: cx - slot / 2, y: pad.top, width: slot, height: innerH, class: 'bar-hit' }, group);
    const text = node('text', { x: cx, y: height - 6, class: 'axis', 'text-anchor': 'middle' }, chart);
    text.textContent = month.label;

    group.addEventListener('pointerenter', () => {
      const box = chart.getBoundingClientRect();
      tip.show(label(month), (cx / width) * box.width, (y(Math.max(month.income, month.expense)) / height) * box.height);
    });
    group.addEventListener('pointerleave', () => tip.hide());
  });

  container.prepend(chart);
}

/** Small progress ring used for goals. */
export function ring(progress, color, size = 76) {
  const radius = size / 2 - 7;
  const circumference = 2 * Math.PI * radius;
  const filled = (Math.min(100, progress) / 100) * circumference;
  return `<svg class="ring" viewBox="0 0 ${size} ${size}" width="${size}" height="${size}" aria-hidden="true">
    <circle cx="${size / 2}" cy="${size / 2}" r="${radius}" class="ring-track"/>
    <circle cx="${size / 2}" cy="${size / 2}" r="${radius}" class="ring-fill" stroke="${color}"
      stroke-dasharray="${filled} ${circumference}" style="--circumference:${circumference}"
      transform="rotate(-90 ${size / 2} ${size / 2})"/>
  </svg>`;
}
