<?php

namespace App\Http\Requests;

use App\Support\YouTube;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VideoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'youtube_link' => ['required', 'string', 'max:500', function (string $attribute, mixed $value, Closure $fail) {
                if (! YouTube::idFrom($value)) {
                    $fail('That is not a YouTube link. Paste the link from YouTube\'s Share button.');
                }
            }],
            'title_en' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'description_en' => ['nullable', 'string', 'max:500'],
            'description_bn' => ['nullable', 'string', 'max:500'],
            'upload_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'duration' => ['nullable', 'regex:/^(\d{1,2}:)?\d{1,2}:[0-5]\d$/'],
            'poster_image' => [
                'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240',
                Rule::dimensions()->minWidth(1280)->maxWidth(6000)->maxHeight(6000)->ratio(16 / 9),
            ],
            'remove_poster' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'youtube_link.required' => 'Paste the YouTube link.',
            'title_en.required' => 'Enter the title in English.',
            'upload_date.date_format' => 'Use a date like 2026-11-05.',
            'upload_date.before_or_equal' => 'The upload date cannot be in the future.',
            'duration.regex' => 'Use minutes and seconds, such as 2:30, or 1:02:30 for hours.',
            'poster_image.dimensions' => 'The poster must be 16:9 and at least 1280 px wide.',
            'poster_image.max' => 'The file is over 10 MB. Export a smaller copy and try again.',
        ];
    }

    /** "2:30" as seconds, or null if blank. */
    public function durationSeconds(): ?int
    {
        if (! $this->filled('duration')) {
            return null;
        }

        return array_reduce(explode(':', $this->input('duration')), fn ($total, $part) => $total * 60 + (int) $part, 0);
    }
}
