<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Turns one upload into the WebP widths the site needs, stored directly in public/uploads
 * (shared hosting has no terminal for storage:link, and no queue: this runs during the request).
 */
class WebpVariants
{
    /**
     * @param  list<int>  $widths  ascending; widths larger than the source are skipped, never upscaled
     * @param  int  $srcWidth  the plain `src` is the largest created width up to this
     * @return array{src: string, srcset: string, w: int, h: int} w and h describe `src`
     */
    public function store(UploadedFile $file, string $folder, string $baseName, array $widths, int $srcWidth, string $field): array
    {
        // shortcut: ignores EXIF rotation, fine for exported renderings; add it if phone photos are uploaded.
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            throw ValidationException::withMessages([$field => 'This image could not be read. Please export it again.']);
        }

        $directory = public_path("uploads/{$folder}");

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $created = [];

        try {
            foreach ($widths as $width) {
                if ($width > imagesx($source)) {
                    continue;
                }

                $scaled = imagescale($source, $width, -1, IMG_BICUBIC);
                $name = "{$baseName}-{$width}.webp";

                if ($scaled === false || ! imagewebp($scaled, "{$directory}/{$name}", 82)) {
                    throw new RuntimeException("Could not write {$name}");
                }

                $created[$width] = "/uploads/{$folder}/{$name}";
            }
        } catch (RuntimeException $e) {
            array_map(fn ($path) => @unlink(public_path($path)), $created);

            throw $e;
        }

        $eligible = array_filter(array_keys($created), fn ($width) => $width <= $srcWidth);
        $chosen = $eligible === [] ? array_key_first($created) : max($eligible);

        return [
            'src' => $created[$chosen],
            'srcset' => implode(', ', array_map(fn ($width, $path) => "{$path} {$width}w", array_keys($created), $created)),
            'w' => $chosen,
            'h' => (int) round($chosen * imagesy($source) / imagesx($source)),
        ];
    }

    /** Removes every uploaded file an image record points to. */
    public function delete(?array $image): void
    {
        foreach (explode(', ', $image['srcset'] ?? '') as $entry) {
            $path = strtok($entry, ' ');

            if ($path && str_starts_with($path, '/uploads/') && ! str_contains($path, '..')) {
                @unlink(public_path($path));
            }
        }
    }
}
