<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCardImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'card_image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'text' => ['nullable', 'string', 'min:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'card_image.required' => 'A business card image is required.',
            'card_image.image' => 'The uploaded file must be an image.',
            'card_image.mimes' => 'The business card must be a JPEG, PNG, JPG, or WEBP image.',
            'card_image.max' => 'The business card image may not be larger than 5 MB.',
            'text.min' => 'The extracted text must be at least 10 characters.',
        ];
    }
}
