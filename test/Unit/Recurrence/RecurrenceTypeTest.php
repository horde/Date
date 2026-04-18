<?php

declare(strict_types=1);

namespace Horde\Date\Test\Unit\Recurrence;

use Horde\Date\Recurrence\RecurrenceType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ValueError;

#[CoversClass(RecurrenceType::class)]
class RecurrenceTypeTest extends TestCase
{
    // =========================================================================
    // Backed values
    // =========================================================================

    #[DataProvider('backedValueProvider')]
    public function testBackedValue(RecurrenceType $case, int $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public static function backedValueProvider(): array
    {
        return [
            'None' => [RecurrenceType::None, 0],
            'Daily' => [RecurrenceType::Daily, 1],
            'Weekly' => [RecurrenceType::Weekly, 2],
            'MonthlyDate' => [RecurrenceType::MonthlyDate, 3],
            'MonthlyWeekday' => [RecurrenceType::MonthlyWeekday, 4],
            'YearlyDate' => [RecurrenceType::YearlyDate, 5],
            'YearlyDay' => [RecurrenceType::YearlyDay, 6],
            'YearlyWeekday' => [RecurrenceType::YearlyWeekday, 7],
            'MonthlyLastWeekday' => [RecurrenceType::MonthlyLastWeekday, 8],
        ];
    }

    // =========================================================================
    // labels
    // =========================================================================

    #[DataProvider('labelProvider')]
    public function testLabel(RecurrenceType $case, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $case->label());
    }

    public static function labelProvider(): array
    {
        return [
            'None' => [RecurrenceType::None, 'None'],
            'Daily' => [RecurrenceType::Daily, 'Daily'],
            'Weekly' => [RecurrenceType::Weekly, 'Weekly'],
            'MonthlyDate' => [RecurrenceType::MonthlyDate, 'Monthly (date)'],
            'MonthlyWeekday' => [RecurrenceType::MonthlyWeekday, 'Monthly (weekday)'],
            'YearlyDate' => [RecurrenceType::YearlyDate, 'Yearly (date)'],
            'YearlyDay' => [RecurrenceType::YearlyDay, 'Yearly (day)'],
            'YearlyWeekday' => [RecurrenceType::YearlyWeekday, 'Yearly (weekday)'],
            'MonthlyLastWeekday' => [RecurrenceType::MonthlyLastWeekday, 'Monthly (last weekday)'],
        ];
    }

    // =========================================================================
    // fromLegacy
    // =========================================================================

    public function testFromLegacyValidValues(): void
    {
        for ($i = 0; $i <= 8; $i++) {
            $type = RecurrenceType::fromLegacy($i);
            $this->assertSame($i, $type->value);
        }
    }

    public function testFromLegacyThrowsOnInvalid(): void
    {
        $this->expectException(ValueError::class);
        RecurrenceType::fromLegacy(99);
    }

    // =========================================================================
    // tryFrom
    // =========================================================================

    public function testTryFromReturnsNullOnInvalid(): void
    {
        $this->assertNull(RecurrenceType::tryFrom(99));
    }

    public function testTryFromReturnsCase(): void
    {
        $this->assertSame(RecurrenceType::Daily, RecurrenceType::tryFrom(1));
    }

    // =========================================================================
    // cases count
    // =========================================================================

    public function testCasesCount(): void
    {
        $this->assertCount(9, RecurrenceType::cases());
    }
}
