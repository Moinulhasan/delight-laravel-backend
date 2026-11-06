<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    private const SLUG = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->input('slug') ?: Str::slug((string) $this->input('name_en')),
            'area_slug' => $this->input('area_slug') ?: Str::slug((string) $this->input('area_name_en')),
        ]);
    }

    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'name_en' => ['required', 'string', 'max:120'],
            'name_bn' => ['nullable', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'regex:'.self::SLUG, Rule::unique('projects', 'slug')->ignore($project)],
            'tagline_en' => ['required', 'string', 'max:200'],
            'tagline_bn' => ['nullable', 'string', 'max:200'],
            'status' => ['required', Rule::in(['ongoing', 'upcoming', 'completed'])],
            'type' => ['required', Rule::in(['residential', 'commercial'])],
            'handover' => ['required', 'digits:4'],
            'area_name_en' => ['required', 'string', 'max:120'],
            'area_name_bn' => ['nullable', 'string', 'max:120'],
            'area_slug' => ['required', 'string', 'max:120', 'regex:'.self::SLUG],
            'address_en' => ['required', 'string', 'max:255'],
            'address_bn' => ['nullable', 'string', 'max:255'],
            'cover_file' => [
                $project ? 'nullable' : 'required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240',
                Rule::dimensions()->minWidth(1200)->maxWidth(6000)->maxHeight(6000),
            ],
            'cover_alt_en' => ['required', 'string', 'max:255'],
            'cover_alt_bn' => ['nullable', 'string', 'max:255'],
            'stats' => ['nullable', 'array', 'max:3'],
            'stats.*.value_en' => ['nullable', 'string', 'max:20', 'required_with:stats.*.label_en,stats.*.label_bn,stats.*.value_bn'],
            'stats.*.value_bn' => ['nullable', 'string', 'max:20'],
            'stats.*.label_en' => ['nullable', 'string', 'max:30', 'required_with:stats.*.value_en,stats.*.value_bn,stats.*.label_bn'],
            'stats.*.label_bn' => ['nullable', 'string', 'max:30'],
            'is_featured' => ['boolean'],
            'seo_title_en' => ['nullable', 'string', 'max:60'],
            'seo_title_bn' => ['nullable', 'string', 'max:60'],
            'seo_description_en' => ['nullable', 'string', 'max:160'],
            'seo_description_bn' => ['nullable', 'string', 'max:160'],
        ];
    }

    public function messages(): array
    {
        return [
            'name_en.required' => 'Enter the project name in English.',
            'slug.required' => 'Enter a web address, or a name in English letters to build it from.',
            'slug.regex' => 'Use lowercase letters, numbers and hyphens, such as delwar-garden.',
            'slug.unique' => 'Another project already uses this web address.',
            'tagline_en.required' => 'Enter the tagline in English.',
            'handover.required' => 'Enter the handover year.',
            'handover.digits' => 'Enter the handover year as 4 digits, such as 2029.',
            'area_name_en.required' => 'Enter the area name in English.',
            'area_slug.required' => 'Enter an area web address, or an area name in English letters.',
            'area_slug.regex' => 'Use lowercase letters, numbers and hyphens, such as bashundhara.',
            'address_en.required' => 'Enter the address in English.',
            'cover_file.required' => 'Choose a cover image.',
            'cover_file.dimensions' => 'The cover must be 1200 to 6000 px wide, and at most 6000 px tall.',
            'cover_file.max' => 'The file is over 10 MB. Export a smaller copy and try again.',
            'cover_alt_en.required' => 'Describe the cover image in English.',
            'stats.*.value_en.required_with' => 'Each card stat needs a value and a label in English.',
            'stats.*.label_en.required_with' => 'Each card stat needs a value and a label in English.',
        ];
    }

    /** Filled rows only, in the website's { value, label } shape. Blank Bangla is left out. */
    public function cardStats(): array
    {
        return collect($this->validated('stats', []))
            ->filter(fn ($stat) => filled($stat['value_en'] ?? null))
            ->map(fn ($stat) => [
                'value' => array_filter(['en' => $stat['value_en'], 'bn' => $stat['value_bn'] ?? null], 'filled'),
                'label' => array_filter(['en' => $stat['label_en'], 'bn' => $stat['label_bn'] ?? null], 'filled'),
            ])
            ->values()
            ->all();
    }
}
