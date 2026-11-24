import assert from 'node:assert/strict';
import test from 'node:test';

import '../../public/js/date-utils.js';

test('local date helpers do not shift a YYYY-MM-DD value through UTC', () => {
  const date = parseLocalDate('2026-11-27');

  assert.equal(date.getFullYear(), 2026);
  assert.equal(date.getMonth(), 10);
  assert.equal(date.getDate(), 27);
  assert.equal(formatLocalDate(date), '2026-11-27');
});

test('1 day quick pick keeps the start date and counts 1 working day', () => {
  const start = '2026-11-27';
  const end = formatLocalDate(endAfterLocal(start, 1));

  assert.equal(end, start);
  assert.equal(countLocalWorkingDays(start, end), 1);
});

test('2 and 3 day quick picks keep the prototype working-day meaning', () => {
  const start = '2026-11-27';

  assert.equal(formatLocalDate(endAfterLocal(start, 2)), '2026-11-30');
  assert.equal(countLocalWorkingDays(start, '2026-11-30'), 2);

  assert.equal(formatLocalDate(endAfterLocal(start, 3)), '2026-12-01');
  assert.equal(countLocalWorkingDays(start, '2026-12-01'), 3);
});

test('1 week quick pick counts 5 working days', () => {
  const start = '2026-11-27';
  const end = formatLocalDate(endAfterLocal(start, 5));

  assert.equal(countLocalWorkingDays(start, end), 5);
});

test('a weekend start follows the prototype endAfter behavior', () => {
  assert.equal(formatLocalDate(endAfterLocal('2026-11-28', 1)), '2026-11-28');
  assert.equal(formatLocalDate(endAfterLocal('2026-11-28', 2)), '2026-11-30');
  assert.equal(formatLocalDate(endAfterLocal('2026-11-28', 5)), '2026-12-03');
});

test('a range spanning a weekend counts only working days', () => {
  assert.equal(countLocalWorkingDays('2026-11-27', '2026-11-30'), 2);
});

test('an end date before the start date has no working days', () => {
  assert.equal(countLocalWorkingDays('2026-11-30', '2026-11-27'), 0);
});
