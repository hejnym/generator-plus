<?php

namespace Mano\GeneratorPlus;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GeneratorPlusTest extends TestCase
{
	/**
	 * @param array<int|string> $sendOnItems Key stands for key of item, value stands for value to be sent.
	 * @param array<int|string> $expectedReturn Key stands for key of item, value stands for value that has been sent.
	 */
    #[DataProvider('provideItems')]
    public function testGeneratorPlus(
        \Closure $generatorClosure,
        array $sendOnItems,
        array $expectedReturn,
    ): void {
        $generator = GeneratorPlus::createFromCallable($generatorClosure);

        $previouslyReturnFromSend = 'UNDEFINED';
        foreach ($generator as $key => $item) {
            if ($previouslyReturnFromSend !== 'UNDEFINED') {
                $this->assertSame(
                    $item,
                    $previouslyReturnFromSend,
                    'Previously returned value from send must be same as next value.',
                );

                $previouslyReturnFromSend = 'UNDEFINED';
            }

            if ((is_string($key) || is_int($key)) && array_key_exists($key, $sendOnItems)) {
                $previouslyReturnFromSend = $generator->sendInForeach($sendOnItems[$key]);
            }
        }

        $this->assertSame($expectedReturn, $generator->getReturn());
    }

    /**
     * @return iterable<mixed>
     */
    public static function provideItems(): iterable
    {
        $simpleGeneratorClosure = function (array $items) {
            return function () use ($items) {
                return self::getSimpleGenerator($items);
            };
        };

        $conditionsGeneratorClosure = function () {
            return self::getConditionalGenerator();
        };

        yield 'empty generator' => [
            $simpleGeneratorClosure([]),
            [],
            [],
        ];

        yield 'generator without keys' => [
            $simpleGeneratorClosure([1, 'ř', 3]),
            [],
            [
                0 => null,
                1 => null,
                2 => null,
            ],
        ];

        yield 'generator with null value' => [
            $simpleGeneratorClosure(['a' => null]),
            [],
            ['a' => null],
        ];

        yield 'generator with alphabetical keys' => [
            $simpleGeneratorClosure(['a' => 1, 'b' => 2, 'c' => 3]),
            [],
            ['a' => null, 'b' => null, 'c' => null],
        ];

        yield 'sent on last position' => [
            $simpleGeneratorClosure(['a' => 1, 'b' => 2]),
            ['b' => 'foo'],
            ['a' => null, 'b' => 'foo'],
        ];

        yield 'send only affects current item' => [
            $simpleGeneratorClosure(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5]),
            [
                'a' => 'sent_for_item_key_a',
                'c' => 'sent_for_item_key_c',
                'e' => 'sent_for_item_key_e',
            ],
            [
                'a' => 'sent_for_item_key_a',
                'b' => null,
                'c' => 'sent_for_item_key_c',
                'd' => null,
                'e' => 'sent_for_item_key_e',
            ],
        ];

        yield 'conditional generator without send' => [
            $conditionsGeneratorClosure,
            [],
            [
                'a' => null,
                'b' => null,
                'c' => null,
                'd' => null,
            ],
        ];

        yield 'conditional generator without extra item' => [
            $conditionsGeneratorClosure,
            ['a' => 'foo', 'b' => 'bar', 'c' => 'baz'],
            [
                'a' => 'foo',
                'b' => 'bar',
                'c' => 'baz',
                'd' => null,
            ],
        ];

        yield 'conditional generator with extra item' => [
            $conditionsGeneratorClosure,
            ['b' => 'add_extra_yield', 'd' => 'baz'],
            [
                'a' => null,
                'b' => 'add_extra_yield',
                'x' => null,
                'c' => null,
                'd' => 'baz',
            ],
        ];

    }

    public function testSerializationNotAllowed(): void
    {
        $generator = GeneratorPlus::createFromCallable(function () {
            yield 1;
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Serialization of 'Generator' is not allowed");

        serialize($generator);
    }

    public function testSentInForeachOnlyOnce(): void
    {
        $generator = GeneratorPlus::createFromCallable(function () {
            yield 1;
        });

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You can only call sendInForeach method once per loop.');

        foreach ($generator as $item) {
            $generator->sendInForeach('1');
            $generator->sendInForeach('2');
        }
    }

    /**
     * @param array<mixed> $items
     */
    private static function getSimpleGenerator(array $items): \Generator
    {
        $sent = [];

        foreach ($items as $key => $item) {
            $sent[$key] = yield $key => $item;
        }

        return $sent;
    }

    private static function getConditionalGenerator(): \Generator
    {
        $return = [];

        $return['a'] = yield 'a' => 1;
        $return['b'] = yield 'b' => 2;

        if ($return['b'] === 'add_extra_yield') {
            $return['x'] = yield 'x' => 'x';
        }

        $return['c'] = yield 'c' => 3;
        $return['d'] = yield 'd' => 4;

        return $return;
    }
}
