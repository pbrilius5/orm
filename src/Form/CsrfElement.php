<?php

declare(strict_types=1);

namespace App\Form;

use Laminas\Form\ElementInterface;
use App\Csrf\CsrfSession;

class CsrfElement implements ElementInterface
{
    private string $name;
    private array $options = [];
    private array $attributes = [];
    private mixed $value = '';
    private ?\Closure $tokenGenerator = null;

    public function __construct(string $name, array $options = [])
    {
        $this->name = $name;
        $this->options = $options;
        $this->attributes['type'] = 'hidden';
        $this->tokenGenerator = $this->createDefaultTokenGenerator();
    }

    private function createDefaultTokenGenerator(): \Closure
    {
        return function (): string {
            return CsrfSession::generateToken();
        };
    }

    public function setTokenGenerator(\Closure $generator): void
    {
        $this->tokenGenerator = $generator;
    }

    public function setName($name): ElementInterface
    {
        $this->name = (string) $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setOptions($options): ElementInterface
    {
        if (is_array($options)) {
            $this->options = $options;
        }
        return $this;
    }

    public function setOption($key, $value): ElementInterface
    {
        $this->options[(string) $key] = $value;
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption($option): mixed
    {
        return $this->options[$option] ?? null;
    }

    public function setAttribute($key, $value): ElementInterface
    {
        $this->attributes[(string) $key] = $value;
        return $this;
    }

    public function getAttribute($key): mixed
    {
        return $this->attributes[(string) $key] ?? null;
    }

    public function hasAttribute($key): bool
    {
        return isset($this->attributes[(string) $key]);
    }

    public function setAttributes($arrayOrTraversable): ElementInterface
    {
        if (is_array($arrayOrTraversable)) {
            foreach ($arrayOrTraversable as $key => $value) {
                $this->attributes[(string) $key] = $value;
            }
        }
        return $this;
    }

    public function getAttributes(): array
    {
        return array_merge($this->attributes, [
            'type' => 'hidden',
            'name' => $this->name,
            'value' => $this->getValue(),
        ]);
    }

    public function setValue($value): ElementInterface
    {
        $this->value = $value;
        return $this;
    }

    public function getValue(): mixed
    {
        if ($this->value !== '') {
            return $this->value;
        }

        return ($this->tokenGenerator)();
    }

    public function getHash(): string
    {
        return (string) $this->getValue();
    }

    public function setLabel($label): ElementInterface
    {
        return $this;
    }

    public function getLabel(): string
    {
        return '';
    }

    public function setMessages($messages): ElementInterface
    {
        return $this;
    }

    public function getMessages(): array
    {
        return [];
    }
}
