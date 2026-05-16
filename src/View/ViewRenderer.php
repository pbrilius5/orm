<?php

declare(strict_types=1);

namespace App\View;

use League\Plates\Engine;

class ViewRenderer
{
    private Engine $engine;

    public function __construct(?string $templatePath = null)
    {
        $this->engine = new Engine($templatePath ?? dirname(__DIR__, 2) . '/templates');
    }

    public function render(string $template, array $data = []): string
    {
        return $this->engine->render($template, $data);
    }

    public function renderWithLayout(string $template, array $data = [], string $layout = 'layout'): string
    {
        return $this->engine->render($layout, array_merge($data, ['content' => $this->engine->render($template, $data)]));
    }
}
