<?php

use Jp7\InterAdmin\FileDatabase;
use Jp7\InterAdmin\FileField;
use Jp7\InterAdmin\FileRecord;
use Jp7\InterAdmin\Record;
use Jp7\InterAdmin\RecordClassMap;
use Jp7\InterAdmin\Type;

/**
 * `file_` is a FIELD prefix on a record table and an ordinary prefix everywhere else, and nothing
 * but this test says so.
 *
 * The framework tables carry a `file_database_id` / `file_id` primary key. Without the opt-out
 * they read back as a FileField object, and the failure lands three layers away as "Object of
 * class FileField could not be converted to int" inside FileDatabase::getBasename() -- the method
 * that builds the S3 object name. It cost 41 tests once.
 *
 * ⚠ Shape cannot substitute for this. A tenant's file field is not always `file_<n>`: ci holds
 * `file_image` and `file_image_th`, so no pattern separates a field from a system column.
 */
class FileFieldPrefixTest extends TestCase
{
    public static function frameworkRecords(): array
    {
        return [
            'files_database' => [new FileDatabase(['file_database_id' => '42'])],
            'files' => [new FileRecord(7)],
        ];
    }

    #[PHPUnit\Framework\Attributes\DataProvider('frameworkRecords')]
    public function testAFrameworkTablesFileColumnStaysAScalar($record): void
    {
        $record->file_id = '42';

        $this->assertSame('42', $record->file_id);
        $this->assertSame(42, (int) $record->file_id, 'the PK has to survive an int cast');
    }

    /**
     * The SELECT side of the same gate. `arquivos()` runs its query through the PARENT record,
     * whose `file_` columns really are fields, so an ungated field list asks the files table
     * for a `file_id_text` twin of its own primary key and 42S22s on every record with files.
     */
    public function testAFilesQueryAsksForNoTextTwinOfThePrimaryKey(): void
    {
        $this->createType(['name' => 'Gallery', 'arquivos' => 'S'], [
            ['type' => 'varchar_key', 'name' => 'Title'],
        ]);
        RecordClassMap::getInstance()->clearCache();

        $gallery = Test_Gallery::build();
        $gallery->title = 'Dia das Crianças';
        $gallery->save();

        $gallery->arquivos()->create(['url' => 'gallery/a.jpg', 'caption' => 'Legenda']);

        $files = $gallery->arquivos()->get();

        $this->assertCount(1, $files);
        $this->assertSame('gallery/a.jpg', $files->first()->url);
        $this->assertSame('Legenda', $files->first()->caption);
    }

    public function testARecordsFileFieldIsStillWrapped(): void
    {
        $record = new Record(['file_1' => 'noticias/a.jpg', 'file_1_text' => 'Legenda'], new Type(0));

        $this->assertInstanceOf(FileField::class, $record->file_1);
        $this->assertSame('Legenda', $record->file_1->text, 'the _text twin is what the wrap is for');
    }
}
