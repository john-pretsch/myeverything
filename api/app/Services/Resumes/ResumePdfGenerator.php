<?php

namespace App\Services\Resumes;

use Dompdf\Dompdf;
use Dompdf\Options;

class ResumePdfGenerator
{
    private const BODY_FONT_SIZE = 11;

    /**
     * Whole-line (case-insensitive) matches treated as section headings,
     * alongside the resume's first line (the candidate's name).
     */
    private const SECTION_HEADERS = [
        'professional experience',
        'work experience',
        'experience',
        'technical skills',
        'skills',
        'education',
        'summary',
        'professional summary',
        'certifications',
        'projects',
    ];

    /**
     * Headers that mark the start of a job-history section, within which
     * the first line after each blank line (before the bullet points) is
     * treated as a job title.
     */
    private const EXPERIENCE_HEADERS = [
        'professional experience',
        'work experience',
        'experience',
    ];

    /**
     * Render plain text resume content into a simple, readable PDF, with
     * the name/section headings bolded and bumped up in size, and job
     * titles (within Professional Experience) bolded.
     */
    public function generate(string $text): string
    {
        return $this->renderHtml($this->toHtml($text));
    }

    public function renderHtml(string $html): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter');
        $dompdf->render();

        return $dompdf->output();
    }

    public function toHtml(string $text, ?string $imageSrc = null): string
    {
        $lines = explode("\n", $text);
        $html = [];

        $isFirstLine = true;
        $inExperienceSection = false;
        $afterBlank = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            $key = mb_strtolower($trimmed);

            if ($isFirstLine && $trimmed !== '') {
                $html[] = '<p class="heading">'.e($trimmed).'</p>';
                $isFirstLine = false;
                $afterBlank = false;

                continue;
            }

            if ($trimmed === '') {
                $html[] = '<div class="spacer"></div>';
                $afterBlank = true;

                continue;
            }

            if (in_array($key, self::SECTION_HEADERS, true)) {
                $html[] = '<p class="heading">'.e($trimmed).'</p>';
                $inExperienceSection = in_array($key, self::EXPERIENCE_HEADERS, true);
                $afterBlank = false;

                continue;
            }

            if ($inExperienceSection && $afterBlank && ! $this->isBulletLine($trimmed)) {
                $html[] = '<p class="job-title">'.e($trimmed).'</p>';
                $afterBlank = false;

                continue;
            }

            $html[] = '<p>'.e($trimmed).'</p>';
            $afterBlank = false;
        }

        $body = ($imageSrc ? '<img class="profile-photo" src="'.e($imageSrc).'" alt="Profile image">' : '').implode('', $html);
        $bodySize = self::BODY_FONT_SIZE;
        $headingSize = self::BODY_FONT_SIZE + 2;

        return <<<HTML
            <html>
            <head>
                <style>
                    body {
                        font-family: Helvetica, Arial, sans-serif;
                        font-size: {$bodySize}pt;
                        line-height: 1.5;
                        color: #111;
                    }
                    p { margin: 0; }
                    .heading { font-weight: bold; font-size: {$headingSize}pt; margin-top: 8pt; }
                    .job-title { font-weight: bold; margin-top: 4pt; }
                    .spacer { height: 6pt; }
                    .profile-photo { width: 160px; height: 160px; float: right; }
                </style>
            </head>
            <body>{$body}</body>
            </html>
            HTML;
    }

    private function isBulletLine(string $line): bool
    {
        return (bool) preg_match('/^[•●○◦\-*]/u', $line);
    }
}
