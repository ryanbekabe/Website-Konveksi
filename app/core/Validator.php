<?php

/**
 * Validasi sederhana.
 * $rules: ['email' => 'required|email|max:100', 'password' => 'required|min:8']
 */
class Validator
{
    private array $errors = [];
    private array $labels;

    public function __construct(private array $data, private array $rules, array $labels = [])
    {
        $this->labels = $labels;
        $this->run();
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $value = trim((string) ($this->data[$field] ?? ''));
            foreach (explode('|', $ruleString) as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $label = $this->labels[$field] ?? $field;

                if ($name === 'required' && $value === '') {
                    $this->errors[$field] = "$label wajib diisi.";
                    break;
                }
                if ($value === '') {
                    continue; // aturan lain hanya berlaku jika terisi
                }
                $error = match ($name) {
                    'email'   => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "$label tidak valid.",
                    'min'     => mb_strlen($value) >= (int) $arg ? null : "$label minimal $arg karakter.",
                    'max'     => mb_strlen($value) <= (int) $arg ? null : "$label maksimal $arg karakter.",
                    'numeric' => is_numeric($value) ? null : "$label harus berupa angka.",
                    'phone'   => preg_match('/^[0-9+\-\s]{8,20}$/', $value) ? null : "$label tidak valid.",
                    'in'      => in_array($value, explode(',', (string) $arg), true) ? null : "$label tidak valid.",
                    default   => null,
                };
                if ($error) {
                    $this->errors[$field] = $error;
                    break;
                }
            }
        }
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
