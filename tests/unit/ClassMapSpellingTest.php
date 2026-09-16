<?php

use Jp7\InterAdmin\RecordClassMap;
use Jp7\InterAdmin\TypeClassMap;

/**
 * A bound name has to answer in BOTH spellings, because the map holds the TENANT's and the caller
 * holds its own app's, and each app runs the other's code.
 *
 * ⚠ The underscore->namespaced arm is what a tenant with MIGRATED bindings depends on. Miss it and
 * the failure is not a wrong answer: getCode() returns null, DynamicLoader declares nothing, and
 * every static finder on that class dies on null. See interadmin's docs/class-binding-rename.md.
 */
class ClassMapSpellingTest extends TestCase
{
    private function bind(string $class, string $classType): Jp7\InterAdmin\Type
    {
        $type = $this->createType(['name' => 'Loja', 'class' => $class, 'class_type' => $classType]);

        RecordClassMap::getInstance()->clearCache();
        TypeClassMap::getInstance()->clearCache();

        return $type;
    }

    /** psr-4 off: the column keeps `Ci_Loja` and the alias bridge makes callers say `Ci\Loja`. */
    public function testAnUnderscoreBindingAnswersItsNamespacedName()
    {
        $type = $this->bind('Ci_Loja', 'Ci_LojaTipo');

        $this->assertEquals($type->type_id, RecordClassMap::getInstance()->getClassTypeId('Ci_Loja'));
        $this->assertEquals($type->type_id, RecordClassMap::getInstance()->getClassTypeId('Ci\Loja'));
        $this->assertEquals($type->type_id, TypeClassMap::getInstance()->getClassTypeId('Ci\LojaTipo'));
    }

    /** The migrated direction, and the one the legacy map was missing. */
    public function testANamespacedBindingAnswersItsUnderscoreName()
    {
        $type = $this->bind('Ci\Loja', 'Ci\LojaTipo');

        $this->assertEquals($type->type_id, RecordClassMap::getInstance()->getClassTypeId('Ci\Loja'));
        $this->assertEquals($type->type_id, RecordClassMap::getInstance()->getClassTypeId('Ci_Loja'));
        $this->assertEquals($type->type_id, TypeClassMap::getInstance()->getClassTypeId('Ci_LojaTipo'));
    }

    /** A name in neither spelling still misses, or the fallback would bind anything to anything. */
    public function testAnUnboundNameStillMisses()
    {
        $this->bind('Ci\Loja', 'Ci\LojaTipo');

        $this->assertFalse(RecordClassMap::getInstance()->getClassTypeId('Ci\Produto'));
        $this->assertFalse(RecordClassMap::getInstance()->getClassTypeId('Ci_Produto'));
    }

    /** DynamicLoader declares the name it was ASKED for, whichever spelling the column holds. */
    public function testTheGeneratedClassCarriesTheNameTheCallerAsked()
    {
        $this->bind('Ci\MochilaoCi\Simulacao', 'Ci\MochilaoCi\SimulacaoTipo');

        $this->assertTrue(class_exists('Ci_MochilaoCi_Simulacao'));
        $this->assertSame('Ci_MochilaoCi_Simulacao', (new ReflectionClass('Ci_MochilaoCi_Simulacao'))->getName());
    }
}
