<?php

declare(strict_types=1);

namespace App\Mcp\Support;

use App\Mcp\Exceptions\McpValidationFailed;

/**
 * Minimal, purpose-built argument validator for MCP tool parameters.
 *
 * Each accessor records a per-key error instead of failing fast, so a single
 * tool call can report every invalid argument at once.
 */
final class McpInput
{
    /**
     * @var array<string, list<string>>
     */
    private array $errors = [];

    /**
     * @var list<string>
     */
    private array $accessed = [];

    /**
     * @param  array<string, mixed>  $input
     */
    private function __construct(private readonly array $input) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function make(array $input): self
    {
        return new self($input);
    }

    public function string(string $key, bool $required = false, int $max = 255): ?string
    {
        $this->accessed[] = $key;

        $value = $this->input[$key] ?? null;

        if ($value === null || $value === '') {
            if ($required) {
                $this->errors[$key][] = 'The field is required.';
            }

            return null;
        }

        if (! is_string($value)) {
            $this->errors[$key][] = 'The field must be a string.';

            return null;
        }

        if (mb_strlen($value) > $max) {
            $this->errors[$key][] = "The field may not be greater than {$max} characters.";

            return null;
        }

        return $value;
    }

    public function int(string $key, bool $required = false): ?int
    {
        $this->accessed[] = $key;

        $value = $this->input[$key] ?? null;

        if ($value === null || $value === '') {
            if ($required) {
                $this->errors[$key][] = 'The field is required.';
            }

            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        $this->errors[$key][] = 'The field must be an integer.';

        return null;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $this->accessed[] = $key;

        $value = $this->input[$key] ?? null;

        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        $this->errors[$key][] = 'The field must be a boolean.';

        return $default;
    }

    /**
     * @param  list<string>  $allowed
     */
    public function oneOf(string $key, array $allowed, bool $required = false, ?string $default = null): ?string
    {
        $value = $this->string($key, $required);

        if ($value === null) {
            return $default;
        }

        if (! in_array($value, $allowed, true)) {
            $this->errors[$key][] = 'The selected value is invalid.';

            return null;
        }

        return $value;
    }

    /**
     * Throw if any accessor recorded an error or an undeclared argument was sent.
     *
     * Tool schemas declare `additionalProperties: false`, so unexpected keys are
     * rejected at the boundary and never reach the application services.
     */
    public function result(): void
    {
        $unexpected = array_diff(array_keys($this->input), $this->accessed);

        foreach ($unexpected as $key) {
            $this->errors[$key][] = 'The field is not allowed.';
        }

        if ($this->errors === []) {
            return;
        }

        throw new McpValidationFailed($this->errors);
    }
}
