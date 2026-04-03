<?php

declare(strict_types=1);

namespace App\View\Helper;

use Laminas\Form\FormInterface;
use Laminas\Form\ElementInterface;

class FormHelper
{
    public static function renderForm(FormInterface $form, array $options = []): string
    {
        $method = strtoupper($form->getAttribute('method') ?? 'POST');
        $action = $form->getAttribute('action') ?? '';
        $class = $form->getAttribute('class') ?? '';
        $attributes = $form->getAttributes();

        $html = '<form method="' . strtolower($method) . '" action="' . htmlspecialchars($action) . '"';
        if ($class) {
            $html .= ' class="' . htmlspecialchars($class) . '"';
        }
        foreach ($attributes as $key => $value) {
            if (!in_array($key, ['method', 'action', 'class', 'name'])) {
                $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
            }
        }
        $html .= '>';

        $errors = $form->getMessages();

        foreach ($form->getElements() as $element) {
            if ($element->getAttribute('type') === 'hidden') {
                $html .= self::renderHiddenElement($element);
                continue;
            }

            if ($element->getAttribute('type') === 'submit') {
                $html .= self::renderSubmitElement($element);
                continue;
            }

            $html .= self::renderField($element, $errors[$element->getName()] ?? []);
        }

        $html .= '</form>';

        return $html;
    }

    private static function renderField(ElementInterface $element, array $errors): string
    {
        $name = $element->getName();
        $type = $element->getAttribute('type') ?? 'text';
        $label = $element->getOption('label') ?? ucfirst($name);
        $rawValue = $element->getValue();
        $value = is_array($rawValue) ? '' : htmlspecialchars($rawValue ?? '');
        $required = $element->getAttribute('required') ? 'required' : '';
        $placeholder = $element->getAttribute('placeholder') ?? '';
        $id = $element->getAttribute('id') ?? $name;
        $hasError = !empty($errors);

        $html = '<div class="mb-3">';
        $html .= '<label for="' . htmlspecialchars($id) . '" class="form-label fw-semibold">' . htmlspecialchars($label) . '</label>';

        if ($type === 'textarea') {
            $html .= '<textarea class="form-control' . ($hasError ? ' is-invalid' : '') . '" id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($name) . '" ' . $required . ' placeholder="' . htmlspecialchars($placeholder) . '">' . $value . '</textarea>';
        } elseif ($type === 'select' || $type === 'multicheckbox' || $type === 'multi_checkbox') {
            $html .= self::renderSelectOrCheckbox($element, $hasError);
        } else {
            $html .= '<input type="' . htmlspecialchars($type) . '" class="form-control' . ($hasError ? ' is-invalid' : '') . '" id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($name) . '" value="' . $value . '" ' . $required . ' placeholder="' . htmlspecialchars($placeholder) . '">';
        }

        if ($hasError) {
            $html .= '<div class="invalid-feedback d-block">';
            foreach ($errors as $error) {
                $html .= '<div>' . htmlspecialchars(is_array($error) ? implode(', ', $error) : $error) . '</div>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    private static function renderSelectOrCheckbox(ElementInterface $element, bool $hasError): string
    {
        $name = $element->getName();
        $valueOptions = $element->getOption('value_options') ?? [];
        $type = $element->getAttribute('type') ?? 'select';
        $class = 'form-control' . ($hasError ? ' is-invalid' : '');

        if ($type === 'multicheckbox' || $type === 'multi_checkbox') {
            $html = '<div class="card border p-3 ' . ($hasError ? 'border-danger' : '') . '">';
            $selectedValues = (array) $element->getValue();
            $disabledValue = 'ROLE_USER';
            foreach ($valueOptions as $value => $label) {
                $checked = in_array($value, $selectedValues) ? 'checked' : '';
                $id = $name . '_' . $value;
                $isLocked = ($value === $disabledValue);
                $disabledAttr = $isLocked ? 'disabled' : '';
                $html .= '<div class="form-check mb-2">';
                $html .= '<input class="form-check-input" type="checkbox" name="' . htmlspecialchars($name) . '[]" value="' . htmlspecialchars($value) . '" id="' . htmlspecialchars($id) . '" ' . $checked . ' ' . $disabledAttr . '>';
                $html .= '<label class="form-check-label' . ($isLocked ? ' text-muted' : '') . '" for="' . htmlspecialchars($id) . '">' . htmlspecialchars($label) . ($isLocked ? ' <small class="text-muted">(required)</small>' : '') . '</label>';
                $html .= '</div>';
            }
            $html .= '</div>';
            return $html;
        }

        $html = '<select class="' . $class . '" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '">';
        $selectedValue = $element->getValue();
        foreach ($valueOptions as $value => $label) {
            $selected = ((string) $value === (string) $selectedValue) ? 'selected' : '';
            $html .= '<option value="' . htmlspecialchars($value) . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $html .= '</select>';

        return $html;
    }

    private static function renderHiddenElement(ElementInterface $element): string
    {
        $name = $element->getName();
        $value = htmlspecialchars($element->getValue() ?? '');
        return '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . $value . '">';
    }

    private static function renderSubmitElement(ElementInterface $element): string
    {
        $value = $element->getValue() ?? $element->getAttribute('value') ?? 'Submit';
        $class = $element->getAttribute('class') ?? 'btn btn-primary';
        return '<div class="d-flex flex-column flex-sm-row gap-2 mt-4"><button type="submit" class="' . htmlspecialchars($class) . '">' . htmlspecialchars($value) . '</button></div>';
    }
}
