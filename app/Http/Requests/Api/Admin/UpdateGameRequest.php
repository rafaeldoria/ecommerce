<?php

namespace App\Http\Requests\Api\Admin;

use App\Modules\Catalog\Models\Game;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gameId = (int) $this->route('game');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Game::class, 'name')->ignore($gameId),
            ],
        ];
    }
}
