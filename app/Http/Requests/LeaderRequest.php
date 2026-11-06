<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LeaderRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (blank($this->input('initials'))) {
            $this->merge(['initials' => self::initialsFrom((string) $this->input('name'))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'initials' => ['required', 'string', 'max:3'],
            'role_en' => ['required', 'string', 'max:120'],
            'role_bn' => ['nullable', 'string', 'max:120'],
            'quote_en' => ['required', 'string', 'max:1000'],
            'quote_bn' => ['nullable', 'string', 'max:1000'],
            'photo_image' => [
                'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240',
                Rule::dimensions()->minWidth(800)->maxWidth(6000)->maxHeight(6000)->ratio(4 / 5),
            ],
            'remove_photo' => ['boolean'],
            'photo_alt_en' => ['nullable', 'string', 'max:255', 'required_with:photo_image'],
            'photo_alt_bn' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Enter the name.',
            'role_en.required' => 'Enter the role in English.',
            'quote_en.required' => 'Enter the message in English.',
            'photo_image.dimensions' => 'The photo must be portrait 4:5, at least 800 px wide.',
            'photo_image.max' => 'The file is over 10 MB. Export a smaller copy and try again.',
            'photo_alt_en.required_with' => 'Describe the photo in English.',
        ];
    }

    /** "Md. Helal Uddin" becomes "HU": titles are left out, up to three letters. */
    public static function initialsFrom(string $name): string
    {
        $words = array_filter(preg_split('/\s+/', trim($name)), fn ($word) => ! in_array(Str::lower(rtrim($word, '.')), ['md', 'mr', 'mrs', 'ms', 'dr', 'engr'], true));

        return Str::upper(implode('', array_map(fn ($word) => Str::substr($word, 0, 1), array_slice(array_values($words), 0, 3))));
    }
}
