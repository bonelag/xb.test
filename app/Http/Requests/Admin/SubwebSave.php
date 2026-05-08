<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubwebSave extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'domain' => 'required|string|max:255|unique:v2_subweb,domain',
            'owner_email' => 'required|email|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'domain.required' => 'Domain不能为空',
            'domain.unique' => 'Domain已存在',
            'owner_email.required' => '邮箱不能为空',
            'owner_email.email' => '邮箱格式不正确',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'data' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
            ], 422)
        );
    }
}
