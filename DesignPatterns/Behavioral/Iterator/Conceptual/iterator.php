<?php

namespace DesignPatterns\Behavioral\Iterator\Conceptual;

/**
 * Concrete Iterators implement various traversal algorithms. These classes
 * store the current traversal position at all times.
 */
class AlphabeticalOrderIterator implements \Iterator
{
    /**
     * @var WordsCollection
     */
    private WordsCollection $collection;

    /**
     * @var int Stores the current traversal position. An iterator may have a
     * lot of other fields for storing iteration state, especially when it is
     * supposed to work with a particular kind of collection.
     */
    private int $position = 0;

    /**
     * @var bool This variable indicates the traversal direction.
     */
    private bool $reverse = false;

    public function __construct(WordsCollection $collection, bool $reverse = false)
    {
        $this->collection = $collection;
        $this->reverse = $reverse;
    }

    public function rewind(): void
    {
        $this->position = $this->reverse
            ? count($this->collection->getItems()) - 1
            : 0;
    }

    public function current(): mixed
    {
        return $this->collection->getItems()[$this->position];
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function next(): void
    {
        $this->position = $this->position + ($this->reverse ? -1 : 1);
    }

    public function valid(): bool
    {
        return isset($this->collection->getItems()[$this->position]);
    }
}

/**
 * Concrete Collections provide one or several methods for retrieving fresh
 * iterator instances, compatible with the collection class.
 */
class WordsCollection implements \IteratorAggregate
{
    private array $items = [];

    public function getItems(): array
    {
        return $this->items;
    }

    public function addItem(string $item): void
    {
        $this->items[] = $item;
    }

    public function getIterator(): \Traversable
    {
        return new AlphabeticalOrderIterator($this);
    }

    public function getReverseIterator(): \Traversable
    {
        return new AlphabeticalOrderIterator($this, true);
    }
}

/**
 * The client code may or may not know about the Concrete Iterator or Collection
 * classes, depending on the level of indirection you want to keep in your
 * program.
 */
$collection = new WordsCollection();
$collection->addItem("First");
$collection->addItem("Second");
$collection->addItem("Third");

echo "Straight traversal:\n";
foreach ($collection->getIterator() as $item) {
    echo $item . "\n";
}

echo "\n";
echo "Reverse traversal:\n";
foreach ($collection->getReverseIterator() as $item) {
    echo $item . "\n";
}
