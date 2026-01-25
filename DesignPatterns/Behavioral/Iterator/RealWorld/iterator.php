<?php

namespace DesignPatterns\Behavioral\Iterator\RealWorld;

/**
 * CSV File Iterator.
 *
 * @author Mykola Prytula
 */
class CsvIterator implements \Iterator
{
    const ROW_SIZE = 4096;

    /**
     * The pointer to the CSV file.
     *
     * @var resource
     */
    protected $filePointer = null;

    /**
     * The current element, which is returned on each iteration.
     *
     * @var array|false|null
     */
    protected array|false|null $currentElement = null;

    /**
     * The row counter.
     *
     * @var int
     */
    protected int $rowCounter = 0;

    /**
     * The delimiter for the CSV file.
     *
     * @var string
     */
    protected string $delimiter = ',';

    /**
     * The constructor tries to open the CSV file. It throws an exception on
     * failure.
     *
     * @param string $file The CSV file.
     * @param string $delimiter The delimiter.
     *
     * @throws \Exception
     */
    public function __construct(string $file, string $delimiter = ',')
    {
        try {
            $this->filePointer = fopen($file, 'rb');
            $this->delimiter = $delimiter;
        } catch (\Exception $e) {
            throw new \Exception('The file "' . $file . '" cannot be read.');
        }
    }

    /**
     * This method resets the file pointer.
     */
    public function rewind(): void
    {
        $this->rowCounter = 0;
        rewind($this->filePointer);
        // Read the first row to initialize
        $this->currentElement = fgetcsv(
            $this->filePointer,
            self::ROW_SIZE,
            $this->delimiter,
            '"',
            '\\'
        );
    }

    /**
     * This method returns the current CSV row as a 2-dimensional array.
     *
     * @return array The current CSV row as a 2-dimensional array.
     */
    public function current(): mixed
    {
        return $this->currentElement ?: [];
    }

    /**
     * This method returns the current row number.
     *
     * @return int The current row number.
     */
    public function key(): mixed
    {
        return $this->rowCounter;
    }

    /**
     * This method moves to the next element.
     */
    public function next(): void
    {
        if (is_resource($this->filePointer)) {
            $this->currentElement = fgetcsv(
                $this->filePointer,
                self::ROW_SIZE,
                $this->delimiter,
                '"',
                '\\'
            );
            $this->rowCounter++;
        }
    }

    /**
     * This method checks if the current position is valid.
     *
     * @return bool If the current position is valid.
     */
    public function valid(): bool
    {
        if ($this->currentElement === false) {
            if (is_resource($this->filePointer)) {
                fclose($this->filePointer);
            }

            return false;
        }

        return is_resource($this->filePointer);
    }
}

/**
 * The client code.
 */
$csv = new CsvIterator(__DIR__ . '/cats.csv');

foreach ($csv as $key => $row) {
    print_r($row);
}
