<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListCatalogProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'game_id' => [
                'nullable',
                'integer',
                Rule::exists('games', 'id')->whereNull('deleted_at'),
            ],
            'rarity_id' => [
                'nullable',
                'integer',
                Rule::exists('rarities', 'id')->whereNull('deleted_at'),
            ],
        ];
    }
}
