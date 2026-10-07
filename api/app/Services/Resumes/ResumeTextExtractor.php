<?php

namespace App\Services\Resumes;

use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

class ResumeTextExtractor
{
    /**
     * Extract plain text from an uploaded PDF, HTML, or plain text resume.
     * Returns null if extraction fails (e.g. an encrypted or malformed PDF)
     * rather than blocking the upload — the original file is still stored.
     */
    public function extract(UploadedFile $file): ?string
    {
        try {
            if ($file->getMimeType() === 'text/plain') {
                return $this->clean(file_get_contents($file->getRealPath()));
            }

            if ($file->getMimeType() === 'text/html') {
                return $this->clean(html_entity_decode(strip_tags(
                    preg_replace('#<(script|style)\b.*?</\1>#is', '', file_get_contents($file->getRealPath())) ?? '',
                )));
            }

            $pdf = (new PdfParser())->parseFile($file->getRealPath());

            return $this->clean($pdf->getText());
        } catch (Throwable) {
            return null;
        }
    }

    private function clean(string $text): string
    {
        $text = str_replace("\r\n", "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
