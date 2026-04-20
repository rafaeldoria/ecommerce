<?php

namespace App\Http\Requests\Api\Admin;

use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
                Rule::unique(Product::class, 'name')->withoutTrashed(),
            ],
            'url_img' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'integer', 'min:0'],
            'game_id' => ['required', 'integer', Rule::exists(Game::class, 'id')->whereNull('deleted_at')],
            'rarity_id' => ['required', 'integer', Rule::exists(Rarity::class, 'id')->whereNull('deleted_at')],
        ];
    }
}
