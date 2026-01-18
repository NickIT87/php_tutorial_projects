<?php

namespace DesignPatterns\Behavioral\Command\RealWorld;

/**
 * The Command interface declares the main execution method.
 */
interface Command
{
    public function execute(): void;
    public function getId(): int;
    public function getStatus(): int;
}

/**
 * Base web scraping command (Template Method).
 */
abstract class WebScrapingCommand implements Command
{
    public int $id;
    public int $status = 0;
    protected string $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getURL(): string
    {
        return $this->url;
    }

    public function execute(): void
    {
        $html = $this->download();
        $this->parse($html);
        $this->complete();
    }

    protected function download(): string
    {
        $html = file_get_contents($this->getURL());
        echo "Downloaded {$this->getURL()}\n";
        return $html ?: '';
    }

    abstract protected function parse(string $html): void;

    protected function complete(): void
    {
        $this->status = 1;
        Queue::get()->completeCommand($this);
    }
}

/**
 * Scrape genre list.
 */
class IMDBGenresScrapingCommand extends WebScrapingCommand
{
    public function __construct()
    {
        parent::__construct("https://www.imdb.com/feature/genre/");
    }

    protected function parse(string $html): void
    {
        preg_match_all(
            '|href="(https://www.imdb.com/search/title\?genres=.*?)"|',
            $html,
            $matches
        );

        echo "Genres found: " . count($matches[1]) . "\n";

        foreach ($matches[1] as $genreUrl) {
            Queue::get()->add(new IMDBGenrePageScrapingCommand($genreUrl));
        }
    }
}

/**
 * Scrape movies by genre (with pagination).
 */
class IMDBGenrePageScrapingCommand extends WebScrapingCommand
{
    private int $page;

    public function __construct(string $url, int $page = 1)
    {
        parent::__construct($url);
        $this->page = $page;
    }

    public function getURL(): string
    {
        return $this->url . '&page=' . $this->page;
    }

    protected function parse(string $html): void
    {
        preg_match_all(
            '|href="(/title/.*?/)\?ref_=adv_li_tt"|',
            $html,
            $matches
        );

        echo "Movies found: " . count($matches[1]) . "\n";

        foreach ($matches[1] as $moviePath) {
            Queue::get()->add(
                new IMDBMovieScrapingCommand(
                    'https://www.imdb.com' . $moviePath
                )
            );
        }

        if (preg_match('|Next &#187;</a>|', $html)) {
            Queue::get()->add(
                new IMDBGenrePageScrapingCommand($this->url, $this->page + 1)
            );
        }
    }
}

/**
 * Scrape movie details.
 */
class IMDBMovieScrapingCommand extends WebScrapingCommand
{
    protected function parse(string $html): void
    {
        if (preg_match('|<h1[^>]*>(.*?)</h1>|', $html, $matches)) {
            echo "Movie parsed: {$matches[1]}\n";
        }
    }
}

/**
 * Queue (Invoker + Persistence).
 */
class Queue
{
    private \SQLite3 $db;

    private function __construct()
    {
        $this->db = new \SQLite3(
            __DIR__ . '/commands.sqlite',
            SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE
        );

        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS commands (
                id INTEGER PRIMARY KEY,
                command TEXT NOT NULL,
                status INTEGER NOT NULL
            )'
        );
    }

    public function isEmpty(): bool
    {
        return (int)$this->db->querySingle(
            'SELECT COUNT(id) FROM commands WHERE status = 0'
        ) === 0;
    }

    public function add(Command $command): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO commands (command, status) VALUES (:command, :status)'
        );

        $stmt->bindValue(':command', base64_encode(serialize($command)));
        $stmt->bindValue(':status', $command->getStatus(), SQLITE3_INTEGER);
        $stmt->execute();
    }

    public function getCommand(): Command
    {
        $record = $this->db->querySingle(
            'SELECT * FROM commands WHERE status = 0 LIMIT 1',
            true
        );

        if ($record === false) {
            throw new \RuntimeException('No pending commands');
        }

        $command = unserialize(base64_decode($record['command']));
        $command->id = (int)$record['id'];

        return $command;
    }

    public function completeCommand(Command $command): void
    {
        $stmt = $this->db->prepare(
            'UPDATE commands SET status = :status WHERE id = :id'
        );

        $stmt->bindValue(':status', $command->getStatus(), SQLITE3_INTEGER);
        $stmt->bindValue(':id', $command->getId(), SQLITE3_INTEGER);
        $stmt->execute();
    }

    public function work(): void
    {
        while (!$this->isEmpty()) {
            $this->getCommand()->execute();
        }
    }

    public static function get(): self
    {
        static $instance;
        return $instance ??= new self();
    }
}

/**
 * Client code.
 */
$queue = Queue::get();

if ($queue->isEmpty()) {
    $queue->add(new IMDBGenresScrapingCommand());
}

$queue->work();
