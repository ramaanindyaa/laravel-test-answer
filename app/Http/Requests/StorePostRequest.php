<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_draft' => 'boolean',
            'published_at' => 'nullable|date|after_or_equal:now',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Post title is required',
            'content.required' => 'Post content is required',
            'published_at.after_or_equal' => 'Published date cannot be in the past',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_draft' => $this->boolean('is_draft', true), // Default to draft
        ]);
    }
}
