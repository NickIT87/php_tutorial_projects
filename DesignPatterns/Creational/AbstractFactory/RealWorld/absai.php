<?php

namespace DesignPatterns\Creational\AbstractFactory\RealWorld;

require __DIR__ . '/../../../vendor/autoload.php';

use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Abstract Factory
 */
interface TemplateFactory
{
    public function createTitleTemplate(): TitleTemplate;
    public function createPageTemplate(): PageTemplate;
    public function getRenderer(): TemplateRenderer;
}

/**
 * Concrete Factory for Twig
 */
class TwigTemplateFactory implements TemplateFactory
{
    public function createTitleTemplate(): TitleTemplate
    {
        return new TwigTitleTemplate();
    }

    public function createPageTemplate(): PageTemplate
    {
        return new TwigPageTemplate($this->createTitleTemplate());
    }

    public function getRenderer(): TemplateRenderer
    {
        return new TwigRenderer();
    }
}

/**
 * Concrete Factory for PHPTemplate
 */
class PHPTemplateFactory implements TemplateFactory
{
    public function createTitleTemplate(): TitleTemplate
    {
        return new PHPTemplateTitleTemplate();
    }

    public function createPageTemplate(): PageTemplate
    {
        return new PHPTemplatePageTemplate($this->createTitleTemplate());
    }

    public function getRenderer(): TemplateRenderer
    {
        return new PHPTemplateRenderer();
    }
}

/**
 * Abstract Product: Title Template
 */
interface TitleTemplate
{
    public function getTemplateString(): string;
}

/**
 * Twig Title Template
 */
class TwigTitleTemplate implements TitleTemplate
{
    public function getTemplateString(): string
    {
        return "<h1>{{ title }}</h1>";
    }
}

/**
 * PHPTemplate Title Template
 */
class PHPTemplateTitleTemplate implements TitleTemplate
{
    public function getTemplateString(): string
    {
        return "<h1><?= \$title; ?></h1>";
    }
}

/**
 * Abstract Product: Page Template
 */
interface PageTemplate
{
    public function getTemplateString(): string;
}

/**
 * Base Page Template
 */
abstract class BasePageTemplate implements PageTemplate
{
    protected TitleTemplate $titleTemplate;

    public function __construct(TitleTemplate $titleTemplate)
    {
        $this->titleTemplate = $titleTemplate;
    }
}

/**
 * Twig Page Template
 */
class TwigPageTemplate extends BasePageTemplate
{
    public function getTemplateString(): string
    {
        $renderedTitle = $this->titleTemplate->getTemplateString();

        return <<<HTML
<div class="page">
    $renderedTitle
    <article class="content">{{ content }}</article>
</div>
HTML;
    }
}

/**
 * PHPTemplate Page Template
 */
class PHPTemplatePageTemplate extends BasePageTemplate
{
    public function getTemplateString(): string
    {
        $renderedTitle = $this->titleTemplate->getTemplateString();

        return <<<HTML
<div class="page">
    $renderedTitle
    <article class="content"><?= \$content; ?></article>
</div>
HTML;
    }
}

/**
 * Abstract Renderer
 */
interface TemplateRenderer
{
    public function render(string $templateString, array $arguments = []): string;
}

/**
 * Twig Renderer
 */
class TwigRenderer implements TemplateRenderer
{
    private Environment $twig;

    public function __construct()
    {
        // Инициализируем Twig с пустым loader
        $this->twig = new Environment(new ArrayLoader([]));
    }

    public function render(string $templateString, array $arguments = []): string
    {
        // Используем ArrayLoader, чтобы рендерить строку
        $this->twig->setLoader(new ArrayLoader(['tpl' => $templateString]));
        return $this->twig->render('tpl', $arguments);
    }
}

/**
 * PHPTemplate Renderer
 */
class PHPTemplateRenderer implements TemplateRenderer
{
    public function render(string $templateString, array $arguments = []): string
    {
        extract($arguments);
        ob_start();
        eval(' ?>' . $templateString . '<?php ');
        $result = ob_get_clean();
        return $result;
    }
}

/**
 * Client
 */
class Page
{
    public string $title;
    public string $content;

    public function __construct(string $title, string $content)
    {
        $this->title = $title;
        $this->content = $content;
    }

    public function render(TemplateFactory $factory): string
    {
        $pageTemplate = $factory->createPageTemplate();
        $renderer = $factory->getRenderer();

        return $renderer->render($pageTemplate->getTemplateString(), [
            'title' => $this->title,
            'content' => $this->content
        ]);
    }
}

/**
 * Example usage
 */
$page = new Page('Sample page', 'This is the body.');

// PHPTemplate rendering
echo "=== PHPTemplate Output ===\n";
echo $page->render(new PHPTemplateFactory());
echo "\n\n";

// Twig rendering
echo "=== Twig Output ===\n";
echo $page->render(new TwigTemplateFactory());
