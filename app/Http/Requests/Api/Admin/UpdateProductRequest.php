<?php

namespace App\Http\Requests\Api\Admin;

use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = (int) $this->route('product');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Product::class, 'name')->ignore($productId)->withoutTrashed(),
            ],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'quantity' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'integer', 'min:0'],
            'game_id' => ['required', 'integer', Rule::exists(Game::class, 'id')->whereNull('deleted_at')],
            'rarity_id' => ['required', 'integer', Rule::exists(Rarity::class, 'id')->whereNull('deleted_at')],
        ];
    }
}
