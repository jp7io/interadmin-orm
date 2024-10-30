<?php

use Jp7\Interadmin\Type;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/

class UnitTester extends \Codeception\Actor
{
    use _generated\UnitTesterActions;

   /**
    * Define custom actions here
    */

    /**
     * Generate InterAdmin types for testing
     * @param array $attributes the name of the type
     * @param array $fields key-value pair of field names and their aliases for use in InterAdmin
     * @return void
     */
    public function createType(array $attributes, array $fields = []): Type
    {
        $type = new Type;
        $classes = [];

        $type->setRawAttributes($attributes + $classes + [
            'class' => 'Test_' . $attributes['nome'],
            'class_type' => 'Test_' . $attributes['nome'] . 'Tipo',
            'mostrar' => 1,
            'deleted_at' => '',
            'campos' => $this->createFields($fields)
        ]);
        $type->save();
        return $type;
    }

    public function createFields(array $fields): string
    {
        // $fields += [
        // ];
        $fieldsVector = [];
        foreach ($fields as $field) {
            $fieldsVector[] = $this->createField($field);
        }
        return interadmin_types_fields_encode($fieldsVector);
    }

    public function createField(array $field): array
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
            'combo' =>  $field['combo'] ?? '',
            'readonly' => $field['readonly'] ?? '',
            'form' => $field['form'] ?? '',
            'label' => $field['label'] ?? '',
            'permissoes' => $field['permissoes'] ?? '',
            'default' => $field['default'] ?? '',
            'nome_id' => $field['nome_id'] ?? to_slug($field['nome'], '_'),
        ];
    }

    public function createUser(array $attributes = []) {
        $user = Test_User::build();
        $attributes += [
            'varchar_key' => 'argentinopam',
            'password_key' => '123',
            'varchar_2' => 'pamela@jp7.com.br',
            'char_key' => 1,
            'publish' => 1,
            'ordem' => 0,
        ];
        $user->setRawAttributes($attributes);
        $user->save();
        return $user;
    }

    public function createUserType() {
        return $this->createType(['nome' => 'User'], [
            ['tipo' => 'varchar_key', 'nome' => 'Username'],
            ['tipo' => 'password_key', 'nome' => 'Password'],
            ['tipo' => 'varchar_2', 'nome' => 'E-mail'],
            ['tipo' => 'char_key', 'nome' => 'Mostrar'],
            ['tipo' => 'int_key', 'nome' => 'Ordem']
        ]);
    }

    public function createI18nNewsType(array $attributes = [])
    {
        return $this->createType(
            $attributes +
            [
                'nome' => 'Noticia'
            ],
            [
                ['tipo' => 'varchar_key', 'nome' => 'Title'],
                ['tipo' => 'char_key', 'nome' => 'Mostrar']
            ]
        );
    }

    public function createUsersBulk($count)
    {
        $list = [];
        for ($i = 0; $i < $count; $i++) {
            $user = $this->createUser([
                'varchar_key' => 'User #' . $i,
                'ordem' => $i
            ]);

            $list[] = $user;
        }

        return $list;
    }

    public function readPrivate($object, $property)
    {
        $reflection = new ReflectionObject($object);
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty->getValue($object);
    }
}
