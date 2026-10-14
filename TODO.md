# TODO

## Loop protection
- A filter that never matches makes `next()` loop forever (the `do … while` in the filter branch has no limit). In CLI workers there's no execution time limit, so the process hangs.
- Picking a limit is hard: steps are per unit (10000 steps ≈ 27 years of days, but < 3 hours of seconds) and a valid but rare filter is indistinguishable from one that never matches.
- Options: generous configurable step limit (like `$MAX_ATTEMPTS`), opt-in limit, or a limit per unit.
- Test: filter returning `false` on `F(never)DT0H0M0S` should throw `Frequency\Exception` (run in a separate process with `set_time_limit()` so it can't hang the suite).

## Fixed values on epoch scopes
- A fixed value in the past returns a date before the start, e.g. `F1W/E` from 2026-01-01 returns 1970-01-01, `F100M/E` returns 1978-05-01.
- A future value is a single, one-off occurrence (`F700M/E` → 2028-05).
- Decide: only accept filters on epoch scopes, or throw when there's no future match. Check stored rules before restricting.

## Rewrite stepping in `next()`
`next()` moves the date with relative steps (`+1 day`, `-1 hour`, `+1 month`) and compares clock values, which breaks in several cases:
- DST spring forward skips days: daily 02:30 (Europe/Brussels) from 2026-03-28 03:00 returns 2026-03-31 instead of 2026-03-29 03:30 (`-1 hour` from 03:00 on the gap day stays 03:00).
- Month overflow: `F31DT0H0M0S` returns 2026-03-03 and 2026-05-01; `F2M30DT0H0M0S` returns 2026-03-02 instead of failing.
- Week 53: `F53WT0H0M0S` from 2026-01-01 returns 2026-12-01 (resetting lower units moves the date out of the matched week and higher units aren't re-checked).
- Month filters without a day rule: `F(apr)M` from 2026-01-31 12:00 returns 2026-04-03 12:00 instead of 2026-04-01 00:00 (filter steps don't reset lower units).
- Approach: cron-style search — set the mismatching unit to its next allowed value, reset lower units, restart from the top on carry.
- Tests to add first:
  - spring forward (`FT2H30M0S`, Europe/Brussels): from 2026-03-28 03:00 → 2026-03-29T03:30+02:00; from 2026-03-29 12:00 → 2026-03-30T02:30+02:00; `between` 2026-03-28..03-31 gives 03-28 02:30, 03-29 03:30, 03-30 02:30
  - fall back (currently passes, keep as guard): `between` 2026-10-24..10-27 gives 02:30+02:00 on 10-24 and 10-25, 02:30+01:00 on 10-26
  - month overflow, week 53 and month filter cases above
  - randomized test comparing `next()` against a brute-force minute-by-minute search

## Other
- Release: PHP >= 8.3 and stricter scope validation are breaking, tag a new major version.
- Check whether [date-frequency-js](https://github.com/smhg/date-frequency-js) has the same bugs.
- Dependabot run on 2026-04-19 failed, check why.
