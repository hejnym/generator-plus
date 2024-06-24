# GeneratorPlus

`GeneratorPlus` is a wrapper around the native `Generator` class that provides additional methods for more control over the iteration process.

## Main Purposes

### 1. Fix the problem with calling `send` method in a `foreach` loop

In native PHP, calling the `send` method inside a `foreach` loop is problematic because it advances the generator to the next yield, disrupting the loop's iteration. `GeneratorPlus` solves this issue with the `sendInForeach` method, which ensures you can send values into the generator without moving to the next yield, maintaining the integrity of the loop.

**Problem Example:**
In this example, calling `send` inside the `foreach` loop causes the generator to advance to the next `yield` statement, disrupting the loop and skipping iterations, leading to unintended behavior.

```php
function getGenerator(): \Generator {
    $sent = [];
    $sent[] = yield 1;
    $sent[] = yield 2;
    $sent[] = yield 3;
    $sent[] = yield 4;
    $sent[] = yield 5;

    return $sent;
}

$generator = getGenerator();

$items = [];
foreach ($generator as $item) {
    $items[] = $item;
    $generator->send(9); // This advances the generator, skipping iterations
}

var_dump($items); // Outputs [1, 3, 5];
var_dump($generator->getReturn()); // Outputs [9, null, 9, null, 9];
```

### 2. Provide mechanism to communicate with the generator caller during the Loop

Unlike the native `getReturn` method, which only allows communication at the end of the generator's execution, `GeneratorPlus` provides a mechanism to communicate with the generator caller during the loop. This reverse communication allows for more dynamic interactions and event handling within the generator lifecycle.

## Installation

You can install `GeneratorPlus` via Composer:

```bash
composer require mano/generator-plus
```

## Usage

### Creating a GeneratorPlus Instance

To create an instance of `GeneratorPlus`, use the `createFromCallable` method, which takes a closure that returns a generator. There are two reasons why a closure must be used instead of creating it directly from the generator:

- Generators cannot be cloned, so using a closure ensures that the original generator is not modified.
- The EventDispatcher must be passed to the generator.

```php
use Mano\GeneratorPlus\GeneratorPlus;
use Mano\GeneratorPlus\EventDispatcher\GeneratorEventDispatcher;

$generatorPlus = GeneratorPlus::createFromCallable(function(GeneratorEventDispatcher $eventDispatcher) {
    yield 1;
    $sent = yield 2;

    if ($sent === 'foo') {
        yield 7;
    }

    $eventDispatcher->dispatch(new MyCustomEvent('There is just one item left!'));

    yield 3;

    return 'bar';
});
```

### Attaching Events

You can attach events to the generator lifecycle using the `attachEvent` method:

```php
use Mano\GeneratorPlus\EventDispatcher\GeneratorPlusEvent;

$generatorPlus->attachEvent(MyCustomEvent::class, function(MyCustomEvent $event) {
    echo "Oh my! The generator wants something! " . $event->getMessage();
});
```

### Iterating with `sendInForeach`

The `sendInForeach` method allows you to send values into the generator within a `foreach` loop without disrupting the loop's iteration:

```php
function getGenerator(): \Generator {
    $sent = [];
    $sent[] = yield 1;
    $sent[] = yield 2;
    $sent[] = yield 3;
    $sent[] = yield 4;
    $sent[] = yield 5;

    return $sent;
}

$generator = getGenerator();

$items = [];
foreach ($generator as $item) {
    $items[] = $item;
    $generator->sendInForeach(9); // This does not advance the generator
}

var_dump($items); // Outputs [1, 2, 3, 4, 5];
var_dump($generator->getReturn()); // Outputs [9, 9, 9, 9, 9];
```

### Real-World Example

When dealing with batch processing in Doctrine, `GeneratorPlus` can come in handy. Usually, you need to flush and clear the entity manager after some batch size to prevent memory issues. Employing the generator's event dispatcher can convey the message that one chunk has been processed to fire an event that clears the entity manager. If you clear the entity manager in the middle of a batch, residual objects would be detached from the manager, leading to errors.

```php
use Mano\GeneratorPlus\GeneratorPlus;
use Mano\GeneratorPlus\EventDispatcher\EventDispatcher;

$generatorPlus = GeneratorPlus::createFromCallable(function(EventDispatcher $eventDispatcher) {
    $counter = 0;

    while (true) {
        $qb = $this->entityManager->createQueryBuilder()
            ->where('...') // select all entities that have not been updated yet
            ->setMaxResults(100);
        
        $result = $qb->getQuery()->getResult();

        if (count($result) === 0) {
            break;
        }

        foreach ($result as $item) {
            yield $item;
            $counter++;
        }

        // let the client code know about reaching the end of the batch
        $eventDispatcher->dispatch(new MyCustomFlushEvent($counter));
    }
});

$generatorPlus->attachEvent(MyCustomFlushEvent::class, function (MyCustomFlushEvent $event) {
    // flush at the end of the batch
    $this->entityManager->flush();
    if ($event->getCount() % 500 === 0) {
        // clear the entity manager
        $this->entityManager->clear();
    }
});

foreach ($generatorPlus as $item) {
    // some batch action
    $item->setFoo(...);
}
```

## License

This project is licensed under the MIT License. See the LICENSE file for details.

## Contributing

Contributions are welcome! Please submit a pull request or open an issue to discuss any changes.