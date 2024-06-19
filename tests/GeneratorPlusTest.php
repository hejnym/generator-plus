<?php

namespace Mano\GeneratorPlus;

use Mano\GeneratorPlus\Exception\GeneratorRewindException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GeneratorPlusTest extends TestCase
{
    /**
     * @param array<mixed> $items
     */
    #[DataProvider('provideItems')]
    public function testPlusBehavesSameAsOriginalGenerator(array $items): void
    {
        $wrapper = GeneratorPlus::createFromGenerator($this->createGeneratorFromArray($items));
        $this->assertGeneratorContent($items, $wrapper);
        $this->assertSame([], $wrapper->getReturn());
    }

    /**
     * @return iterable<mixed>
     */
    public static function provideItems(): iterable
    {
        yield [[]];

        yield [[null]];

        yield [[1, 2, 3]];

        yield [['a', 'ž', '3']];

        yield [['a' => 1, 'b' => 'FOO', 3 => null]];
    }

    public function testPlusSendBehavesSameAsOriginalGenerator(): void
    {
        $wrapper = GeneratorPlus::createFromGenerator($this->createGeneratorFromArray([1, 2, 3]));

        foreach ($wrapper as $item) {
            $wrapper->send('sent_for_item_' . $item);
        }

        $this->assertSame([
            0 => 'sent_for_item_1',
            2 => 'sent_for_item_3',
        ], $wrapper->getReturn());
    }

    public function testSerializationNotAllowed(): void
    {
        $wrapper = GeneratorPlus::createFromGenerator($this->createGeneratorFromArray([1, 2, 3]));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Serialization of 'Generator' is not allowed");

        serialize($wrapper);
    }

    public function testCreateFromCallable(): void
    {
        $instance = GeneratorPlus::createFromCallable(function () {
            yield 1;
            yield 2;
            yield 3;
        });

        $this->assertGeneratorContent([1,2,3], $instance);
    }

    public function testGeneratorMustNotBeRunBefore(): void
    {
        $generator = $this->createGeneratorFromArray([1, 2, 3]);
        $generator->next();


        $this->expectException(GeneratorRewindException::class);

        GeneratorPlus::createFromGenerator($generator);
    }

    /**
     * @param array<mixed> $items
     */
    private function createGeneratorFromArray(array $items): \Generator
    {
        $sent = [];

        foreach ($items as $key => $item) {
            $sent[] = yield $key => $item;
        }

        return array_filter($sent);
    }

    /**
     * @param array<mixed> $expected
     * @param \Traversable<mixed> $actual
     */
    private function assertGeneratorContent(array $expected, \Traversable $actual): void
    {
        $this->assertSame(
            $expected,
            iterator_to_array($actual),
        );
    }
}
