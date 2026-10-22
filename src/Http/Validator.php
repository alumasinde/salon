<?php

declare(strict_types=1);

namespace Salon\Http;

use Salon\Support\Clock;
use Salon\Support\Color;
use Salon\Support\Phone;

/** Tiny rule-based validator: "field" => "required|max:120|phone". */
final class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data, array $rules): self
    {
        $v = new self($data);
        foreach ($rules as $field => $ruleString) {
            foreach (explode('|', $ruleString) as $rule) {
                $v->apply($field, $rule);
            }
        }
        return $v;
    }

    private function apply(string $field, string $rule): void
    {
        [$name, $limit] = array_pad(explode(':', $rule, 2), 2, null);
        $raw = $this->data[$field] ?? '';
        $value = is_string($raw) ? trim($raw) : (string) $raw;
        $label = ucfirst(str_replace('_', ' ', $field));
        switch ($name) {
            case 'required':
                if ($value === '') {
                    $this->errors[$field] = "Enter $label.";
                }
                break;
            case 'max':
                if (mb_strlen($value) > (int) $limit) {
                    $this->errors[$field] = "$label is too long (max $limit characters).";
                }
                break;
            case 'min':
                if (mb_strlen($value) < (int) $limit) {
                    $this->errors[$field] = "$label must be at least $limit characters.";
                }
                break;
            case 'int':
                if ($value !== '' && !preg_match('/^-?\d+$/', $value)) {
                    $this->errors[$field] = "$label must be a whole number.";
                }
                break;
            case 'phone':
                if ($value !== '' && Phone::normalize($value) === null) {
                    $this->errors[$field] = 'Enter a Kenyan number, like 0712 345 678.';
                }
                break;
            case 'date':
                $d = \DateTime::createFromFormat('!Y-m-d', $value);
                if ($value !== '' && ($d === false || $d->format('Y-m-d') !== $value)) {
                    $this->errors[$field] = "Choose a valid $label.";
                }
                break;
            case 'time':
                if ($value !== '' && Clock::to24($value) === null) {
                    $this->errors[$field] = "Choose a valid $label.";
                }
                break;
            case 'hex':
                if ($value !== '' && !Color::isHex($value)) {
                    $this->errors[$field] = "$label must be a colour like #d6336c.";
                }
                break;
            case 'email':
                if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                    $this->errors[$field] = 'Enter a valid email address.';
                }
                break;
            case 'in':
                $allowed = explode(',', (string) $limit);
                if ($value !== '' && !in_array($value, $allowed, true)) {
                    $this->errors[$field] = "Pick a valid $label.";
                }
                break;
        }
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function first(): string
    {
        return (string) ($this->errors ? reset($this->errors) : '');
    }

    public function value(string $field, string $default = ''): string
    {
        $raw = $this->data[$field] ?? $default;
        return is_string($raw) ? trim($raw) : (string) $raw;
    }

    public function int(string $field, int $default = 0): int
    {
        $value = $this->value($field, (string) $default);
        return is_numeric($value) ? (int) $value : $default;
    }
}
