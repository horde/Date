# Upgrading Horde_Date

Contact: dev@lists.horde.org

This lists the API changes between releases of the package.

---

## Upgrading to 3.0.0

Horde_Date 3.0 introduces modern PSR-4 classes alongside the legacy API.
Two upgrade paths are available depending on whether you can adopt the new
class model or need to stay type-compatible.

### New classes

- `Horde\Date\Date` — Immutable date class extending `DateTimeImmutable`.
  Implements `Horde\Date\DateInterface`.

- `Horde\Date\HordeLegacyDate` — Mutable drop-in replacement for
  `Horde_Date`. Extends `Horde_Date` but stores all state in a private
  `DateTimeImmutable`. The parent's protected fields are dead storage;
  all reads and writes go through the internal immutable.

- `Horde\Date\Recurrence\Recurrence` — Modern recurrence engine with typed
  API, backed enum and `DateTimeImmutable` throughout.

- `Horde_Date_Recurrence` — Now a thin wrapper delegating to
  `Horde\Date\Recurrence\Recurrence`. Preserves the legacy public API.

### Behavioral change in HordeLegacyDate: overflow direction

`HordeLegacyDate` replaces the legacy `_correct()` overflow engine with
PHP's native `DateTimeImmutable::setDate()` / `setTime()` normalization.
The two engines agree in almost all cases. They diverge when a date component
is **decreased** past a month boundary with a day that exceeds the target
month's length:

```php
// March 31 minus one month
$d = new Horde_Date('2026-03-31');
$d->month = 2;
// Horde_Date:         2026-02-28 (clamped backward)
// HordeLegacyDate:    2026-03-03 (overflowed forward)

// Leap-year edge: Feb 29 on a leap year, year decreased to non-leap
$d = new Horde_Date('2028-02-29');
$d->year = 2026;
// Horde_Date:         2026-02-26 (clamped backward)
// HordeLegacyDate:    2026-03-01 (overflowed forward)
```

The legacy engine uses a `$down` flag in `_correct()` that subtracts the
difference in month lengths when the value decreased. PHP's date math always
overflows forward. The forward-overflow behavior is more predictable and
matches what `DateTime` / `DateTimeImmutable` produce natively.

This affects code that:

- Sets `->month` to a lower value on a date with day 29–31.
- Calls `sub(['month' => N])` when the start day exceeds the target month.
- Sets `->year` to a lower value on Feb 29 of a leap year.

### Upgrade path A: Adopt the modern model (preferred)

Replace `Horde_Date` usage with `Horde\Date\Date` (immutable) or native
`DateTimeImmutable`. This is the recommended path for new code and for
existing code that can tolerate an API change.

**What changes:**

1. Construction from components uses a formatted string or
   `DateTimeImmutable::createFromFormat()`:

   ```php
   // Before
   $d = new Horde_Date(['year' => 2026, 'month' => 4, 'mday' => 17]);

   // After
   use Horde\Date\Date;
   $d = new Date('2026-04-17');
   ```

2. Property mutation becomes `with*` / `set*` returning new instances:

   ```php
   // Before
   $d->month = 6;
   $d->setTimezone('Europe/Berlin');

   // After
   $d = $d->setDate(2026, 6, 17);
   $d = $d->setTimezone(new DateTimeZone('Europe/Berlin'));
   ```

3. Arithmetic uses `addParts()` / `subParts()` instead of `add()` with
   arrays:

   ```php
   // Before
   $result = $d->add(['month' => 1, 'mday' => 5]);

   // After
   $result = $d->addParts(months: 1, days: 5);
   ```

4. Comparison accepts `DateTimeInterface`:

   ```php
   // Before (required Horde_Date or auto-constructed)
   $d->compareDate($other);

   // After (any DateTimeInterface)
   $d->compareDate($other);
   ```

5. `strftime()` is removed. Use `format()` with `DateTimeFormatter` or
   `IcuFormatter` for locale-aware output:

   ```php
   // Before
   $d->strftime('%B %d, %Y');

   // After
   use Horde\Date\Formatter\IcuFormatter;
   $d->format('MMMM dd, yyyy', IcuFormatter::class, 'en_US');
   ```

6. `Horde_Date_Recurrence` callers should migrate to
   `Horde\Date\Recurrence\Recurrence`, which uses `DateTimeImmutable`
   throughout and `RecurrenceType` enum instead of integer constants.

**Benefits:**

- Immutability prevents accidental state corruption.
- Full `DateTimeInterface` compatibility (pass to any PHP API).
- No `__get` / `__set` overhead.
- Overflow semantics match PHP's standard library exactly.

### Upgrade path B: Drop-in replacement with caveats (compromise)

Replace `new Horde_Date(...)` with `new HordeLegacyDate(...)` in your
code, or configure your autoloader / DI container to substitute the class.
`HordeLegacyDate` extends `Horde_Date` and passes `instanceof` checks.

**What works identically:**

- All property reads and writes (`$d->year`, `$d->month = 13`, etc.).
- Overflow normalization for increases (month 13 → January next year, day 32 →
  next month, hour 25 → next day, etc.).
- `add()` / `sub()` with integer seconds.
- `add()` / `sub()` with array factors, **except** the down-clamping case.
- `setTimezone()` (converts wall-clock time).
- `$d->timezone = 'X'` (reinterprets wall-clock time in new zone).
- All formatting (`format()`, `strftime()`, `__toString()`).
- All comparison methods.
- All serialization (`timestamp()`, `toJson()`, `toiCalendar()`, etc.).
- All conversion (`toDateTime()`, `toDateTimeImmutable()`, `toDate()`).
- All calendar calculations (`dayOfWeek()`, `toDays()`, `weekOfYear()`,
  `setNthWeekday()`, etc.).
- `clone` produces independent copies.

**What changes:**

- Decreasing `->month` or `->year` when day exceeds the target month's
  length overflows forward instead of clamping backward. See the behavioral
  change section above.
- `sub(['month' => 1])` from March 31 yields March 3 instead of Feb 28.
- The `_correct()` method on the parent is never called. Subclasses that
  override `_correct()` will not see their override invoked.
- Protected fields `$_year`, `$_month`, `$_mday`, `$_hour`,
  `$_min`, `$_sec` are stale after construction. Code that accesses these
  directly (bypassing `__get`) will read incorrect values.

**Recommended guard for the overflow difference:**

If your code subtracts months and relies on day clamping, clamp explicitly:

```php
use Horde\Date\HordeLegacyDate;
use Horde\Date\Utils;

$d = new HordeLegacyDate('2026-03-31');
$targetMonth = $d->month - 1;
$d->month = $targetMonth;
// If day overflowed past the target month, clamp back
if ($d->month !== $targetMonth) {
    $d->mday = Utils::daysInMonth($targetMonth, $d->year);
    $d->month = $targetMonth;
}
```

### Upgrading Horde_Date_Recurrence

`Horde_Date_Recurrence` is now a thin wrapper over
`Horde\Date\Recurrence\Recurrence`. The public API is unchanged. Callers
that mutate `->start` or `->recurEnd` through the property and then call
methods on the result should use the setter instead:

```php
// Before (silently broken with wrapper — mutates a detached copy)
$event->recurrence->start->setTimezone($tz);

// After (safe with both legacy and wrapper)
$event->recurrence->setRecurStart(
    (clone $event->recurrence->start)->setTimezone($tz)
);
```

The `fromRRule10()` method now correctly parses `MONTHLY_LAST_WEEKDAY`
rules (the legacy implementation had a trim bug that mapped them to
`MONTHLY_WEEKDAY`).

---

## Upgrading to 2.4.0

- Horde_Date_Recurrence
  - `toHash()`, `fromHash()` — These methods have been added.

## Upgrading to 2.3.0

- Horde_Date
  - `getTimezoneAlias()` — This method has been added.

## Upgrading to 2.2.0

- Horde_Date
  - `isEqual()` — This method has been added.
  - `setNthWeekday()` — The `$nth` parameter accepts negative values.

- Horde_Recurrence
  - `RECUR_MONTHLY_LAST_WEEKDAY` — This constant has been added as a
    possible value for the `$recurType` property.

## Upgrading to 2.0.0

- Horde_Date_Recurrence
  - `toHash()`, `fromHash()` — These methods have been renamed to
    `toKolab()` and `fromKolab()`, because the hash format is
    really Kolab-specific.
