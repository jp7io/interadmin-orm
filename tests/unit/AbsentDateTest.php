<?php

use Jp7\InterAdmin\Record;

/**
 * A NULL datetime and the '0000-00-00' sentinel are ONE absent value, and the columns are being
 * converted from the second to the first (docs/zero-date-plan.md in the interadmin repo).
 *
 * ⚠ The whole reason this needs a test rather than a null passthrough: `new \Date(null)` is NOW.
 * So a null publish_at would read as "published this second" and a null expire_at as
 * "expires this second" -- every record on a converted tenant silently unpublished, with nothing
 * thrown and nothing logged.
 */
class AbsentDateTest extends TestCase
{
    public static function absentDateColumns(): array
    {
        return [
            'expire_at' => ['expire_at'],
            'publish_at' => ['publish_at'],
            'a tenant date slot' => ['date_1'],
        ];
    }

    #[PHPUnit\Framework\Attributes\DataProvider('absentDateColumns')]
    public function testANullDateColumnReadsAsAbsentRatherThanNow(string $column): void
    {
        $record = new Record();
        $record->setRawAttributes([$column => null]);

        $this->assertSame(-1, (int) $record->$column->format('Y'), 'a null date is the absent year');
        $this->assertLessThan(0, $record->$column->getTimestamp());
    }

    /** Both encodings have to land on the same object, or a reader has to know which tenant it is on. */
    public function testTheSentinelAndNullAgree(): void
    {
        $fromNull = new Record();
        $fromNull->setRawAttributes(['expire_at' => null]);

        $fromSentinel = new Record();
        $fromSentinel->setRawAttributes(['expire_at' => '0000-00-00 00:00:00']);

        $this->assertSame(
            $fromSentinel->expire_at->getTimestamp(),
            $fromNull->expire_at->getTimestamp()
        );
    }

    /** The guard is the absent value only: a real date still reads as itself. */
    public function testARealDateIsUntouched(): void
    {
        $record = new Record();
        $record->setRawAttributes(['expire_at' => '2026-09-03 21:00:00']);

        $this->assertSame('2026-09-03 21:00:00', $record->expire_at->format('Y-m-d H:i:s'));
    }
}
