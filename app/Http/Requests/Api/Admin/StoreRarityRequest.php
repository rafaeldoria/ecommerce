<?php

namespace App\Http\Requests\Api\Admin;

use App\Modules\Catalog\Models\Rarity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRarityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Rarity::class, 'name'),
            ],
        ];
    }
}
