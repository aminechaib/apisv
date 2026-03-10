<?php

// app/Services/OcrService.php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrService
{
    /**
     * Extracts text from an image file using an OCR service.
     *
     * @param  string  $imagePath  The path to the image file in storage.
     * @return string|null The extracted text, or null on failure.
     */
    public static function extractText(string $imagePath): ?string
    {
        if (! is_file($imagePath)) {
            Log::warning('OCR image file missing: '.$imagePath);

            return null;
        }

        try {
            $text = (new TesseractOCR($imagePath))
                ->lang('eng')
                ->run();

            $text = trim((string) $text);

            return $text === '' ? null : $text;
        } catch (\Throwable $e) {
            // Most common local failure: the `tesseract` binary is not installed / not in PATH.
            Log::warning('OCR failed: '.$e->getMessage());

            return null;
        }
    }
}
