<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TopupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric',
            'payment_method_id' => 'required|numeric|exists:payment_methods,id',
            'payment_account_id' => 'required|numeric|exists:payment_accounts,id',
            'transaction_number' => 'required|numeric|digits:6',
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'amount.required' => 'The amount field is required.',
            'amount.numeric' => 'The amount field must be a number.',
            'payment_method_id.required' => 'The payment method field is required.',
            'payment_method_id.numeric' => 'The payment method field must be a number.',
            'payment_method_id.exists' => 'The selected payment method is invalid.',

            'payment_account_id.required' => 'The payment account field is required.',
            'payment_account_id.numeric' => 'The payment account field must be a number.',
            'payment_account_id.exists' => 'The selected payment account is invalid.',

            'transaction_number.required' => 'The transaction number field is required.',
            'transaction_number.numeric' => 'The transaction number field must be a number.',
            'transaction_number.digits' => 'The transaction number field must be 6 digits.',

            'payment_proof.required' => 'The payment proof field is required.',
            'payment_proof.image' => 'The payment proof field must be an image.',
            'payment_proof.mimes' => 'The payment proof field must be a file of type: jpeg, png, jpg, gif.',
        ];
    }
}
