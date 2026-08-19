<?php

use Jp7\Interadmin\Query;
use Jp7\Interadmin\Query\BaseQuery;
use Jp7\Interadmin\Query\TypeQuery;
use Jp7\Interadmin\Record;
use Jp7\Interadmin\Type;

/**
 * The `@method static` tags on Type and Record are the only written form of an API PHP resolves
 * at call time, so nothing but this test can tell a correct tag from a fictional one.
 *
 * Three ways a tag goes wrong, one per check below. The middle one is the trap that has already
 * cost a release: a PUBLIC INSTANCE method of the same name does not fall through to
 * __callStatic() at all -- PHP raises "Non-static method cannot be called statically" -- so a tag
 * for one describes a call that can only fatal. Type::first() and Record::save() are both in that
 * position today, which is why neither is declared.
 */
class MagicSurfaceTest extends TestCase
{
    /** Each class's __callStatic() forwards to a query of its own, and only these two do. */
    private const FORWARDS = [
        Type::class => TypeQuery::class,
        Record::class => Query::class,
    ];

    public function testEveryDeclaredStaticMethodIsOneTheQueryAnswers(): void
    {
        $fictional = [];

        foreach (self::FORWARDS as $class => $query) {
            $answered = $this->publicMethods($query);

            foreach ($this->declaredStaticMethods($class) as $name) {
                if (!in_array($name, $answered, true)) {
                    $fictional[] = "$class::$name() -> $query has no such public method";
                }
            }
        }

        $this->assertSame([], $fictional, "A @method static tag names nothing to forward to, so the "
            ."call reaches BaseQuery::__call() and throws BadMethodCallException:\n"
            .implode("\n", $fictional));
    }

    public function testNoDeclaredStaticMethodIsShadowedByAPublicInstanceMethod(): void
    {
        $shadowed = [];

        foreach (array_keys(self::FORWARDS) as $class) {
            $reflection = new ReflectionClass($class);

            foreach ($this->declaredStaticMethods($class) as $name) {
                if (!$reflection->hasMethod($name)) {
                    continue;
                }
                $own = $reflection->getMethod($name);

                if ($own->isPublic() && !$own->isStatic()) {
                    $shadowed[] = "$class::$name()";
                }
            }
        }

        $this->assertSame([], $shadowed, "A @method static tag names a public INSTANCE method, so "
            ."PHP raises Error rather than calling __callStatic():\n".implode("\n", $shadowed));
    }

    public function testEveryMethodTheQueryAnswersIsDeclared(): void
    {
        $undeclared = [];

        foreach (self::FORWARDS as $class => $query) {
            $declared = $this->declaredStaticMethods($class);
            $reflection = new ReflectionClass($class);

            foreach ($this->publicMethods($query) as $name) {
                if ($reflection->hasMethod($name) && $reflection->getMethod($name)->isPublic()) {
                    continue; // answered by the class itself, statically or not at all
                }
                if (!in_array($name, $declared, true)) {
                    $undeclared[] = "$class::$name() -> $query::$name()";
                }
            }
        }

        $this->assertSame([], $undeclared, "A query method is reachable through __callStatic() and "
            ."undeclared. Add `@method static <return> <name>(...)`, or make the query method "
            ."non-public if it was never meant to be reached that way:\n".implode("\n", $undeclared));
    }

    /**
     * BaseQuery::__call() answers `orFoo()` for any `foo()` it declares, which is a second
     * undeclared surface -- and one that only works inside a where() grouping closure, so it
     * belongs on the query rather than on Type/Record.
     */
    public function testEveryOrVariantBaseQueryDeclaresHasAMethodToForwardTo(): void
    {
        $reflection = new ReflectionClass(BaseQuery::class);
        $fictional = [];

        foreach ($this->declaredMethods(BaseQuery::class) as $name) {
            $original = lcfirst(substr($name, 2));

            if (!str_starts_with($name, 'or') || !$reflection->hasMethod($original)) {
                $fictional[] = $name;
            }
        }

        $this->assertSame([], $fictional, "BaseQuery declares a @method __call() cannot answer -- "
            ."it forwards `orFoo()` to `foo()` and nothing else:\n".implode("\n", $fictional));
    }

    /**
     * `@method static foo()` is read as a STATIC method named foo by one parser and as an
     * instance method returning static by the next, so it says nothing either way. Spell the
     * return type: `@method static self foo()` for the static one, `@method $this foo()` for
     * the fluent instance one. BaseQuery's or-variants were written the ambiguous way first,
     * and every check above silently skipped them.
     */
    public function testNoMethodTagUsesTheAmbiguousStaticForm(): void
    {
        $ambiguous = [];

        foreach ([...array_keys(self::FORWARDS), ...array_values(self::FORWARDS), BaseQuery::class] as $class) {
            $doc = (new ReflectionClass($class))->getDocComment() ?: '';

            if (preg_match_all('/^\s*\*\s*@method\s+static\s+(?<name>\w+)\s*\(/m', $doc, $matches)) {
                foreach ($matches['name'] as $name) {
                    $ambiguous[] = "$class::$name()";
                }
            }
        }

        $this->assertSame([], $ambiguous, "A @method tag names `static` and then the method, with "
            ."no return type between them, so whether it declares a static method is up to the "
            ."parser:\n".implode("\n", $ambiguous));
    }

    /** @return string[] */
    private function publicMethods(string $class): array
    {
        $names = [];
        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (!str_starts_with($method->getName(), '__')) {
                $names[] = $method->getName();
            }
        }
        return $names;
    }

    /** @return string[] */
    private function declaredStaticMethods(string $class): array
    {
        return $this->parseMethodTags($class, true);
    }

    /** @return string[] */
    private function declaredMethods(string $class): array
    {
        return $this->parseMethodTags($class, false);
    }

    /**
     * @param bool $static Whether to keep the `@method static` tags or the instance ones.
     * @return string[]
     */
    private function parseMethodTags(string $class, bool $static): array
    {
        $names = [];

        for ($reflection = new ReflectionClass($class); $reflection; $reflection = $reflection->getParentClass()) {
            $doc = $reflection->getDocComment();
            if (!$doc) {
                continue;
            }
            preg_match_all(
                '/^\s*\*\s*@method\s+(?<static>static\s+)?(?:[^\s(]+\s+)?(?<name>\w+)\s*\(/m',
                $doc,
                $matches,
                PREG_SET_ORDER
            );
            foreach ($matches as $match) {
                if ((bool) trim($match['static']) === $static) {
                    $names[] = $match['name'];
                }
            }
        }

        return array_values(array_unique($names));
    }
}
