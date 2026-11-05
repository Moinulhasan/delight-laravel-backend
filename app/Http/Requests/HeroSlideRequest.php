<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class HeroSlideRequest extends FormRequest
{
    public function rules(): array
    {
        $desktopRequired = $this->route('hero_slide') ? 'nullable' : 'required';

        return [
            'desktop_image' => [
                $desktopRequired, 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240',
                Rule::dimensions()->minWidth(1920)->maxWidth(6000)->maxHeight(6000),
                $this->orientation('landscape'),
            ],
            'mobile_image' => [
                'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240',
                Rule::dimensions()->minWidth(720)->maxWidth(6000)->maxHeight(6000),
                $this->orientation('portrait'),
            ],
            'remove_mobile' => ['boolean'],
            'alt_en' => ['required', 'string', 'max:255'],
            'alt_bn' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'desktop_image.required' => 'Choose a desktop image.',
            'desktop_image.max' => 'The file is over 10 MB. Export a smaller copy and try again.',
            'desktop_image.dimensions' => 'The desktop image must be 1920 to 6000 px wide, and at most 6000 px tall.',
            'mobile_image.max' => 'The file is over 10 MB. Export a smaller copy and try again.',
            'mobile_image.dimensions' => 'The mobile image must be 720 to 6000 px wide, and at most 6000 px tall.',
            'alt_en.required' => 'Describe the image in English.',
        ];
    }

    private function orientation(string $expected): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($expected) {
            $size = $value instanceof UploadedFile ? @getimagesize($value->getRealPath()) : false;

            if ($size && ($expected === 'landscape' ? $size[0] <= $size[1] : $size[0] >= $size[1])) {
                $fail("This image must be {$expected}.");
            }
        };
    }
}
