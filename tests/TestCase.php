<?php

use Jp7\Interadmin\Type;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base class for the ORM suite, carrying what used to be split across Codeception's
 * UnitTester actor and its Db module.
 *
 * The Db module was configured `populate: true, cleanup: true`, i.e. reload the dump before
 * every test. The dump is pure DDL -- four tables, zero rows -- so the same isolation comes
 * from loading it once in bootstrap and truncating between tests, which is far cheaper.
 */
abstract class TestCase extends BaseTestCase
{
    /** The tables in tests/_data/dump.sql, emptied before each test. */
    private const TABLES = [
        'interadmin_teste_registros',
        'interadmin_teste_en_registros',
        'interadmin_teste_tags',
        'interadmin_teste_tipos',
    ];

    private static ?PDO $pdo = null;

    /**
     * Drops and rebuilds the test database from the dump. Called once, from bootstrap.php.
     */
    public static function createSchema(): void
    {
        $config = testDatabaseConfig();

        $server = new PDO(
            "mysql:host={$config['host']};port={$config['port']}",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $server->exec("DROP DATABASE IF EXISTS `{$config['database']}`");
        $server->exec("CREATE DATABASE `{$config['database']}` DEFAULT CHARACTER SET utf8mb4");

        self::pdo()->exec(file_get_contents(BASE_PATH.'/tests/_data/dump.sql'));
    }

    protected static function pdo(): PDO
    {
        if (!self::$pdo) {
            $config = testDatabaseConfig();
            self::$pdo = new PDO(
                "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
                $config['username'],
                $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        }
        return self::$pdo;
    }

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::TABLES as $table) {
            self::pdo()->exec("TRUNCATE TABLE `{$table}`");
        }
    }

    // ---------------------------------------------------------------- database assertions

    protected function seeNumRecords(int $expected, string $table): void
    {
        $this->assertSame(
            $expected,
            (int) self::pdo()->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn(),
            "Unexpected number of rows in {$table}"
        );
    }

    protected function seeInDatabase(string $table, array $criteria): void
    {
        $this->assertGreaterThan(
            0,
            $this->countInDatabase($table, $criteria),
            "No row in {$table} matching ".json_encode($criteria)
        );
    }

    protected function dontSeeInDatabase(string $table, array $criteria): void
    {
        $this->assertSame(
            0,
            $this->countInDatabase($table, $criteria),
            "Unexpected row in {$table} matching ".json_encode($criteria)
        );
    }

    private function countInDatabase(string $table, array $criteria): int
    {
        $where = [];
        foreach (array_keys($criteria) as $column) {
            $where[] = "`{$column}` = ?";
        }

        $statement = self::pdo()->prepare(
            "SELECT COUNT(*) FROM `{$table}` WHERE ".implode(' AND ', $where)
        );
        $statement->execute(array_values($criteria));

        return (int) $statement->fetchColumn();
    }

    /**
     * PHPUnit's own expectException() applies to the whole test method, so it cannot express
     * "this call throws, and then the test carries on" -- which is what Codeception's
     * two-argument expectException() did and what several tests here rely on.
     */
    protected function assertThrows(string $exception, callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            $this->assertInstanceOf($exception, $e);
            return;
        }
        $this->fail('Expected '.$exception.' was not thrown.');
    }

    protected function readPrivate(object $object, string $property): mixed
    {
        $reflectionProperty = (new ReflectionObject($object))->getProperty($property);
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty->getValue($object);
    }

    // ------------------------------------------------------------------------- factories

    /**
     * Generate InterAdmin types for testing.
     *
     * @param array $attributes the name of the type
     * @param array $fields     key-value pair of field names and their aliases for use in InterAdmin
     */
    protected function createType(array $attributes, array $fields = []): Type
    {
        $type = new Type;

        $type->setRawAttributes($attributes + [
            'class' => 'Test_'.$attributes['nome'],
            'class_tipo' => 'Test_'.$attributes['nome'].'Tipo',
            'mostrar' => 'S',
            'deleted_tipo' => '',
            'campos' => $this->createFields($fields)
        ]);
        $type->save();

        return $type;
    }

    protected function createFields(array $fields): string
    {
        $fieldsVector = [];
        foreach ($fields as $field) {
            $fieldsVector[] = $this->createField($field);
        }
        return interadmin_tipos_campos_encode($fieldsVector);
    }

    protected function createField(array $field): array
    {
        return [
            'ordem' => 1,
            'tipo' => $field['tipo'],
            'nome' => $field['nome'],
            'ajuda' => $field['ajuda'] ?? '',
            'tamanho' => $field['tamanho'] ?? '',
            'obrigatorio' => $field['obrigatorio'] ?? '',
            'separador' => $field['separator'] ?? '',
            'xtra' => $field['xtra'] ?? '',
            'lista' => $field['lista'] ?? '',
            'orderby' => $field['orderby'] ?? '',
            'combo' => $field['combo'] ?? '',
            'readonly' => $field['readonly'] ?? '',
            'form' => $field['form'] ?? '',
            'label' => $field['label'] ?? '',
            'permissoes' => $field['permissoes'] ?? '',
            'default' => $field['default'] ?? '',
            'nome_id' => $field['nome_id'] ?? to_slug($field['nome'], '_'),
        ];
    }

    protected function createUser(array $attributes = [])
    {
        $user = Test_User::build();
        $attributes += [
            'varchar_key' => 'argentinopam',
            'password_key' => '123',
            'varchar_2' => 'pamela@jp7.com.br',
            'char_key' => 'S',
            'publish' => 'S',
            'ordem' => 0,
        ];
        $user->setRawAttributes($attributes);
        $user->save();

        return $user;
    }

    protected function createUserType(): Type
    {
        return $this->createType(['nome' => 'User'], [
            ['tipo' => 'varchar_key', 'nome' => 'Username'],
            ['tipo' => 'password_key', 'nome' => 'Password'],
            ['tipo' => 'varchar_2', 'nome' => 'E-mail'],
            ['tipo' => 'char_key', 'nome' => 'Mostrar'],
            ['tipo' => 'int_key', 'nome' => 'Ordem']
        ]);
    }

    protected function createI18nNewsType(array $attributes = []): Type
    {
        return $this->createType(
            $attributes + ['nome' => 'Noticia'],
            [
                ['tipo' => 'varchar_key', 'nome' => 'Title'],
                ['tipo' => 'char_key', 'nome' => 'Mostrar']
            ]
        );
    }

    protected function createUsersBulk(int $count): array
    {
        $list = [];
        for ($i = 0; $i < $count; $i++) {
            $list[] = $this->createUser([
                'varchar_key' => 'User #'.$i,
                'ordem' => $i
            ]);
        }

        return $list;
    }
}
