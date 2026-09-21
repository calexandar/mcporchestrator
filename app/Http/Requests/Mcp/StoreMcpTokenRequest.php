<?php

declare(strict_types=1);

namespace App\Http\Requests\Mcp;

use App\Mcp\Auth\McpScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMcpTokenRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['string', 'in:'.implode(',', McpScope::values())],
            'days' => ['nullable', 'integer', 'min:0', 'max:3650'],
        ];
    }
}
