import { test } from 'node:test';
import assert from 'node:assert/strict';
import { STRINGS } from '../i18n.js';

test('English and Dutch have the same keys', () => {
  const en = Object.keys(STRINGS.en).sort();
  const nl = Object.keys(STRINGS.nl).sort();
  assert.deepEqual(nl, en);
});

test('placeholders match between languages', () => {
  const names = (text) => [...text.matchAll(/\{(\w+)\}/g)].map((m) => m[1]).sort();
  for (const key of Object.keys(STRINGS.en)) {
    assert.deepEqual(names(STRINGS.nl[key]), names(STRINGS.en[key]), key);
  }
});
