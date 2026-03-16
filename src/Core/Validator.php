<?php
namespace App\Core;

use App\Core\Database;

class Validator {
    private array $errors = [];
    private array $data;

    public function __construct(array $data) {
        $this->data = $data;
    }

    public static function make(array $data, array $rules): self {
        $v = new self($data);
        foreach ($rules as $field => $ruleStr) {
            $fieldRules = is_array($ruleStr) ? $ruleStr : explode('|', $ruleStr);
            foreach ($fieldRules as $rule) {
                $v->applyRule($field, trim($rule));
            }
        }
        return $v;
    }

    private function applyRule(string $field, string $rule): void {
        $value = $this->data[$field] ?? null;
        $strVal = is_array($value) ? '' : (string)($value ?? '');

        if ($rule === 'required') {
            if ($strVal === '' || $value === null) {
                $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' is required.');
            }
            return;
        }

        // Skip other validations if field is empty and not required
        if ($strVal === '' && $value === null) return;

        if ($rule === 'email') {
            if (!filter_var($strVal, FILTER_VALIDATE_EMAIL)) {
                $this->addError($field, 'Please enter a valid email address.');
            }
        } elseif (strncmp($rule, 'min:', 4) === 0) {
            $min = (int)substr($rule, 4);
            if (mb_strlen($strVal) < $min) {
                $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be at least $min characters.");
            }
        } elseif (strncmp($rule, 'max:', 4) === 0) {
            $max = (int)substr($rule, 4);
            if (mb_strlen($strVal) > $max) {
                $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must not exceed $max characters.");
            }
        } elseif ($rule === 'numeric') {
            if (!is_numeric($strVal)) {
                $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' must be a number.');
            }
        } elseif ($rule === 'integer') {
            if (!ctype_digit($strVal)) {
                $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' must be a whole number.');
            }
        } elseif (strncmp($rule, 'in:', 3) === 0) {
            $allowed = explode(',', substr($rule, 3));
            if (!in_array($strVal, $allowed, true)) {
                $this->addError($field, 'Invalid selection for ' . str_replace('_', ' ', $field) . '.');
            }
        } elseif (strncmp($rule, 'regex:', 6) === 0) {
            $pattern = substr($rule, 6);
            if (!preg_match($pattern, $strVal)) {
                $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' format is invalid.');
            }
        } elseif ($rule === 'confirmed') {
            $confirm = $this->data[$field . '_confirmation'] ?? null;
            if ($strVal !== (string)($confirm ?? '')) {
                $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' confirmation does not match.');
            }
        } elseif (strncmp($rule, 'unique:', 7) === 0) {
            // unique:table:column or unique:table:column:ignore_id
            $parts  = explode(':', substr($rule, 7));
            $table  = $parts[0];
            $column = $parts[1] ?? $field;
            $ignore = $parts[2] ?? null;
            $sql    = "SELECT COUNT(*) FROM `$table` WHERE `$column` = ?";
            $params = [$strVal];
            if ($ignore) {
                $sql    .= " AND id != ?";
                $params[] = $ignore;
            }
            $count = (int)Database::fetchScalar($sql, $params);
            if ($count > 0) {
                $this->addError($field, 'That ' . str_replace('_', ' ', $field) . ' is already taken.');
            }
        } elseif ($rule === 'phone') {
            // Simple SA phone validation: +27/0 and 9-10 digits
            $cleaned = preg_replace('/\D/', '', $strVal);
            if (!preg_match('/^(27|0)\d{9}$/', $cleaned)) {
                $this->addError($field, 'Please enter a valid phone number.');
            }
        } elseif ($rule === 'url') {
            if (!filter_var($strVal, FILTER_VALIDATE_URL)) {
                $this->addError($field, 'Please enter a valid URL.');
            }
        }
    }

    private function addError(string $field, string $message): void {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    public function fails(): bool {
        return !empty($this->errors);
    }

    public function passes(): bool {
        return empty($this->errors);
    }

    public function errors(): array {
        return $this->errors;
    }

    public function first(string $field): ?string {
        return $this->errors[$field] ?? null;
    }

    /**
     * Return only the validated fields from the input data.
     */
    public function validated(array $fields): array {
        return array_intersect_key($this->data, array_flip($fields));
    }
}
