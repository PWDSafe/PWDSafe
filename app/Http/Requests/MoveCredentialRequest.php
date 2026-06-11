<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveCredentialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('update', $this->route('credential'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'group_id' => 'required|integer|exists:groups,id',
            'encrypted' => 'required|array|min:1',
            'encrypted.*.userid' => 'required|integer|exists:users,id',
            'encrypted.*.data' => 'required|string',
        ];
    }
}
