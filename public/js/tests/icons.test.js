import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { CATEGORY_ICONS, icon } from '../icons.js';

test('the dashboard knows every icon the API accepts', () => {
  const php = readFileSync(new URL('../../../app/Http/Requests/CategoryRequest.php', import.meta.url), 'utf8');
  const allowed = [...php.match(/ICONS = \[([\s\S]*?)\]/)[1].matchAll(/'(\w+)'/g)].map((m) => m[1]);
  assert.deepEqual([...CATEGORY_ICONS].sort(), allowed.sort());
  for (const name of allowed) assert.ok(!icon(name).includes('r="4"/>') || name === 'dot', name);
});
