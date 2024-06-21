<?php

declare(strict_types=1);

namespace Mano\GeneratorPlus;

use Iterator;
use Closure;
use Generator;
use Mano\GeneratorPlus\EventDispatcher\GeneratorEventDispatcher;
use Mano\GeneratorPlus\EventDispatcher\GeneratorPlusEvent;

/**
 * @template TKey
 * @template TValue
 * @template TSend
 * @template TReturn
 * @implements Iterator<TKey,TValue>
 * @mixin Generator<TKey,TValue, TSend, TReturn>
 */
final class GeneratorPlus implements Iterator
{
    /**
     * @param Generator<TKey, TValue, TSend, TReturn> $generator
     */
    private Generator $generator;
    private mixed $current;
    private mixed $key;

    private bool $skipMovingToNext = false;
    private GeneratorEventDispatcher $eventDispatcher;

    /**
     * @param Generator<TKey, TValue, TSend, TReturn> $generator
     * @param GeneratorEventDispatcher $eventDispatcher
     */
    private function __construct(
        Generator $generator,
        GeneratorEventDispatcher $eventDispatcher,
    ) {

        $this->generator = $generator;

        $this->current = $this->generator->current();
        $this->key = $this->generator->key();
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param-immediately-invoked-callable $callable
     * @param callable(GeneratorEventDispatcher): Generator<TKey, TValue, TSend, TReturn> $callable
     * @return self<TKey, TValue, TSend, TReturn>
     */
    public static function createFromCallable(callable $callable): self
    {
        $eventDispatcher = new GeneratorEventDispatcher();
        $generator = call_user_func($callable, $eventDispatcher);

        return new self($generator, $eventDispatcher);
    }

    /**
     * @param TSend $value
     * @return TValue|null
     */
    public function sendInForeach(mixed $value): mixed
    {
        if ($this->skipMovingToNext === true) {
            throw new \LogicException('You can only call sendInForeach method once per loop.');
        }

        $value = $this->send($value);
        $this->skipMovingToNext = true;

        return $value;
    }

    /**
     * @template TClosureEvent of GeneratorPlusEvent
     * @param class-string<TClosureEvent> $eventName
     * @param Closure(TClosureEvent): void $closure
     */
    public function attachEvent(string $eventName, Closure $closure): void
    {
        $this->eventDispatcher->attach($eventName, $closure);
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        $this->generator->rewind();
        $this->current = $this->generator->current();
        $this->key = $this->generator->key();
    }

    /**
     * @inheritDoc
     * @return TValue
     */
    public function current(): mixed
    {
        return $this->current;
    }

    /**
     * @inheritDoc
     * @return TKey
     */
    public function key(): mixed
    {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    public function next(): void
    {
        if ($this->skipMovingToNext === false) {
            $this->generator->next();
        } else {
            $this->skipMovingToNext = false;
        }

        $this->current = $this->generator->current();
        $this->key = $this->generator->key();
    }

    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        return $this->generator->valid();
    }

    /**
     * It leads to faulty results if called within foreach loop. Use {@see self::sendInForeach } is such case.
     * @see Generator::send()
     * @param TSend $value
     * @return TValue|null
     */
    public function send(mixed $value): mixed
    {
        return $this->generator->send($value);
    }

    /**
     * @see Generator::getReturn()
     * @return TReturn $value
     */
    public function getReturn(): mixed
    {
        return $this->generator->getReturn();
    }

    /**
     * @see Generator::__wakeup()
     */
    public function __wakeup(): void
    {
        $this->generator->__wakeup();
    }
}
