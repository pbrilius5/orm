<?php

declare(strict_types=1);

namespace App\View;

use Oryx\Mvc\View\ViewInterface;
use Oryx\Mvc\View\PlatesView;

class ViewRenderer implements ViewInterface
{
    private PlatesView $plates;

    public function __construct(string $templatePath = null)
    {
        $this->plates = new PlatesView($templatePath ?? dirname(__DIR__, 2) . '/templates');
    }

    public function render(string $template, array $data = []): string
    {
        return $this->plates->render($template, $data);
    }

    public function renderWithLayout(string $template, array $data = [], string $layout = 'layout'): string
    {
        return $this->plates->render($layout, array_merge($data, ['content' => $this->plates->render($template, $data)]));
    }
}
