<?php

declare(strict_types=1);

namespace Mano\GeneratorPlus\EventDispatcher;

use SplObjectStorage;
use Closure;

class GeneratorEventDispatcher
{
    /**
     * @var SplObjectStorage<Closure, class-string<GeneratorPlusEvent>>
     */
    private SplObjectStorage $observers;

    public function __construct()
    {
        $this->observers = new \SplObjectStorage();
    }

    /**
     * @template TClosureEvent of GeneratorPlusEvent
     * @param class-string<TClosureEvent> $eventName
     * @param Closure(TClosureEvent): void $observer
     */
    public function attach(string $eventName, \Closure $observer): void
    {
        $this->observers->attach($observer, $eventName);
    }

    public function dispatch(GeneratorPlusEvent $event): void
    {
        foreach ($this->observers as $observer) {
            if($this->observers->offsetGet($observer) === get_class($event)) {
                $observer($event);
            }
        }
    }
}
