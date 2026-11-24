(function (root) {
  'use strict';

  function pad(value) {
    return String(value).padStart(2, '0');
  }

  // Build dates from their local Y-M-D parts. Never parse "YYYY-MM-DD"
  // as a UTC instant, because that shifts the date back in UTC+8.
  function parseLocalDate(value) {
    const parts = String(value).split('-').map(Number);
    return new Date(parts[0], parts[1] - 1, parts[2]);
  }

  function formatLocalDate(date) {
    return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
  }

  function addLocalDays(date, amount) {
    const copy = new Date(date.getTime());
    copy.setDate(copy.getDate() + amount);
    return copy;
  }

  function isWorkingDay(date) {
    const day = date.getDay();
    return day !== 0 && day !== 6;
  }

  function countLocalWorkingDays(startValue, endValue) {
    if (!startValue || !endValue) return 0;

    const start = parseLocalDate(startValue);
    const end = parseLocalDate(endValue);

    if (start > end) return 0;

    let count = 0;
    let cursor = new Date(start.getTime());

    while (cursor <= end) {
      if (isWorkingDay(cursor)) count++;
      cursor = addLocalDays(cursor, 1);
    }

    return count;
  }

  // Mirrors the prototype's endAfter(s, n): the start day is slot 1.
  // Extra calendar days are added until n working-day slots are filled.
  function endAfterLocal(startValue, days) {
    const start = parseLocalDate(startValue);
    let cursor = new Date(start.getTime());
    let counted = 1;

    while (counted < days) {
      cursor = addLocalDays(cursor, 1);
      if (isWorkingDay(cursor)) counted++;
    }

    return cursor;
  }

  root.parseLocalDate = parseLocalDate;
  root.formatLocalDate = formatLocalDate;
  root.addLocalDays = addLocalDays;
  root.countLocalWorkingDays = countLocalWorkingDays;
  root.endAfterLocal = endAfterLocal;
})(typeof window !== 'undefined' ? window : globalThis);
