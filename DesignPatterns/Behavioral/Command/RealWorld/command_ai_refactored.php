<?php

namespace DesignPatterns\Behavioral\Command\RealWorld;

/**
 * Command interface
 */
interface Command
{
    public function execute(): void;
    public function getId(): int;
    public function getStatus(): int;
}

/**
 * Base command (Template Method)
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

    public function execute(): void
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0 (X11; Linux x86_64)\r\n"
            ]
        ]);

        $html = file_get_contents($this->url, false, $context);

        $this->parse($html ?: '');
        $this->complete();
    }

    abstract protected function parse(string $html): void;

    protected function complete(): void
    {
        $this->status = 1;
        Queue::get()->completeCommand($this);
    }
}

/**
 * Scrape Wikipedia category list
 */
class WikiCategoryListCommand extends WebScrapingCommand
{
    public function __construct()
    {
        parent::__construct('https://en.wikipedia.org/wiki/Category:Computer_science');
    }

    protected function parse(string $html): void
    {
        preg_match_all(
            '|href="(/wiki/Category:[^"]+)"|',
            $html,
            $matches
        );

        $categories = array_unique($matches[1]);

        echo "Categories found: " . count($categories) . PHP_EOL;

        foreach (array_slice($categories, 0, 5) as $path) {
            Queue::get()->add(
                new WikiCategoryPageCommand(
                    'https://en.wikipedia.org' . $path
                )
            );
        }
    }
}

/**
 * Scrape pages inside category
 */
class WikiCategoryPageCommand extends WebScrapingCommand
{
    protected function parse(string $html): void
    {
        preg_match_all(
            '|href="(/wiki/[^":]+)"|',
            $html,
            $matches
        );

        $pages = array_unique($matches[1]);

        echo "Pages found: " . count($pages) . PHP_EOL;

        foreach (array_slice($pages, 0, 5) as $page) {
            Queue::get()->add(
                new WikiPageCommand(
                    'https://en.wikipedia.org' . $page
                )
            );
        }
    }
}

/**
 * Scrape single wiki page
 */
class WikiPageCommand extends WebScrapingCommand
{
    protected function parse(string $html): void
    {
        if (preg_match('|<h1[^>]*>(.*?)</h1>|', $html, $matches)) {
            echo "Article: " . strip_tags($matches[1]) . PHP_EOL;
        }
    }
}

/**
 * Queue (Invoker)
 */
class Queue
{
    private \SQLite3 $db;

    private function __construct()
    {
        $this->db = new \SQLite3(__DIR__ . '/commands.sqlite');

        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS commands (
                id INTEGER PRIMARY KEY,
                command TEXT NOT NULL,
                status INTEGER NOT NULL
            )'
        );
    }

    public function add(Command $command): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO commands (command, status) VALUES (:c, :s)'
        );

        $stmt->bindValue(':c', base64_encode(serialize($command)));
        $stmt->bindValue(':s', $command->getStatus());
        $stmt->execute();
    }

    public function getCommand(): Command
    {
        $row = $this->db->querySingle(
            'SELECT * FROM commands WHERE status = 0 LIMIT 1',
            true
        );

        if (!$row) {
            throw new \RuntimeException('Queue empty');
        }

        $command = unserialize(base64_decode($row['command']));
        $command->id = (int)$row['id'];

        return $command;
    }

    public function completeCommand(Command $command): void
    {
        $stmt = $this->db->prepare(
            'UPDATE commands SET status = 1 WHERE id = :id'
        );
        $stmt->bindValue(':id', $command->getId());
        $stmt->execute();
    }

    public function isEmpty(): bool
    {
        return (int)$this->db->querySingle(
            'SELECT COUNT(*) FROM commands WHERE status = 0'
        ) === 0;
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
 * Client code
 */
$queue = Queue::get();

if ($queue->isEmpty()) {
    $queue->add(new WikiCategoryListCommand());
}

$queue->work();
