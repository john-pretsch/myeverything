<?php

namespace App\Services\Resumes;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ResumeTailoringService
{
    /**
     * Ask ChatGPT to rewrite a resume so it better targets a specific job.
     * Returns the tailored resume as plain text.
     */
    public function tailor(string $resumeText, string $jobTitle, string $company, string $jobDescription): string
    {
        $apiKey = config('services.openai.api_key');

        if (! $apiKey) {
            throw new RuntimeException('OpenAI API key is not configured. Add OPENAI_API_KEY to .env.');
        }

        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'temperature' => 0.4,
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => $this->userPrompt($resumeText, $jobTitle, $company, $jobDescription)],
                ],
            ]);

        if (! $response->successful()) {
            $message = $response->json('error.message') ?? ('HTTP '.$response->status());

            throw new RuntimeException('ChatGPT request failed: '.$message);
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('ChatGPT returned an empty response.');
        }

        return trim($content);
    }

    private function systemPrompt(): string
    {
        return <<<'TEXT'
            You tailor resumes to specific job postings. Rewrite the given
            resume so it emphasizes the experience and skills most relevant
            to the job description, using clearer and more targeted
            language and reordering content where that helps.

            Do not invent employers, job titles, dates, degrees,
            certifications, or skills that are not present in the original
            resume — only rephrase, reorder, and re-emphasize what is
            already there.

            Return only the rewritten resume as plain text. No commentary,
            no markdown formatting, no headers explaining what you did.
            TEXT;
    }

    private function userPrompt(string $resumeText, string $jobTitle, string $company, string $jobDescription): string
    {
        return "Job title: {$jobTitle}\nCompany: {$company}\n\nJob description:\n{$jobDescription}\n\nOriginal resume:\n{$resumeText}";
    }
}
