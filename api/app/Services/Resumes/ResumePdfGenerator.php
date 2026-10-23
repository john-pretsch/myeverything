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

    /**
     * Add a "Download PDF" link to a rendered HTML resume. The link is hidden
     * when the page itself is printed.
     */
    public function withDownloadLink(string $html, string $href): string
    {
        $link = '<style>.download-pdf{margin:12px 0 0;font-size:.95rem;font-weight:600}'
            .'@media print{.download-pdf{display:none}}</style>'
            .'<p class="download-pdf"><a href="'.e($href).'" download>Download PDF</a></p>';

        $withLink = preg_replace('#</header>#i', $link.'</header>', $html, 1, $count);

        return $count > 0
            ? $withLink
            : (preg_replace('#<body[^>]*>#i', '$0'.$link, $html, 1) ?? $html);
    }

    /**
     * Restyle an HTML resume for print: no images, black on white, serif type,
     * and only CSS 2.1 layout so Dompdf can render it faithfully.
     */
    public function toPrintHtml(string $html): string
    {
        $html = preg_replace('#<style\b.*?</style>#is', '', $html) ?? $html;
        $html = preg_replace('#<p>\s*<img\b[^>]*>\s*</p>#i', '', $html) ?? $html;
        $html = preg_replace('#<img\b[^>]*>#i', '', $html) ?? $html;
        $html = preg_replace('#<footer\b.*?</footer>#is', '', $html) ?? $html;

        $css = <<<'CSS'
            @page { margin: 0.55in; }
            body { font-family: "Times New Roman", Times, serif; font-size: 10.5pt; line-height: 1.35; color: #000; background: #fff; margin: 0; }
            a { color: #000; text-decoration: none; }
            h1, h2, h3, p, ul { margin: 0; }
            header { border-bottom: 1.5pt solid #000; padding-bottom: 8pt; margin-bottom: 4pt; }
            .name { font-size: 24pt; font-weight: bold; }
            .title { font-size: 11.5pt; font-style: italic; margin: 3pt 0 4pt; }
            .contact span { margin-right: 14pt; }
            section { margin-top: 12pt; }
            .section-heading { font-size: 10.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1pt; border-bottom: 0.5pt solid #000; padding-bottom: 2pt; margin-bottom: 6pt; }
            .skill { margin-bottom: 4pt; }
            .skill strong, .highlight strong { font-weight: bold; }
            .skill strong { display: block; }
            .highlight { margin-bottom: 5pt; }
            .job { margin-bottom: 10pt; page-break-inside: avoid; }
            .job-title { font-size: 11pt; font-weight: bold; float: left; width: 72%; }
            .dates { float: right; text-align: right; width: 26%; font-style: italic; }
            .job-header { margin-bottom: 3pt; overflow: hidden; }
            .job-location { font-style: italic; margin-bottom: 3pt; clear: both; }
            ul { padding-left: 16pt; margin-top: 3pt; clear: both; }
            li { margin: 2pt 0; }
            .download-pdf { display: none; }
            CSS;

        return preg_replace('#</head>#i', '<style>'.$css.'</style></head>', $html, 1, $n)
            ?: '<html><head><style>'.$css.'</style></head>'.$html.'</html>';
    }

    private function isBulletLine(string $line): bool
    {
        return (bool) preg_match('/^[•●○◦\-*]/u', $line);
    }
}
