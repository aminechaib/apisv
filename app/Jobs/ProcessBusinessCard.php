<?php

namespace App\Jobs;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessBusinessCard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    protected Contact $contact;

    protected string $extractedText;

    /**
     * Create a new job instance.
     */
    public function __construct(Contact $contact, string $extractedText)
    {
        $this->contact = $contact;
        $this->extractedText = $extractedText;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->attempts() > 1) {
            Log::info("Retrying Mistral AI Job for contact ID {$this->contact->id}. Attempt: ".$this->attempts());
        } else {
            Log::info("Mistral AI Job Started for contact ID {$this->contact->id}");
        }

        Log::info("Received Text for Processing: \n---\n".$this->extractedText."\n---");

        $systemPrompt = 'You are a JSON-only data extraction service. You will be given raw text from a business card. Your only job is to return a single, valid JSON object with the extracted data. Do not output any other text, explanations, or markdown.';

        $userPrompt = "From the following text, extract the data into this exact JSON format:
        {\"name\":\"\", \"email\":\"\", \"phone\":\"\", \"company\":\"\", \"activity\":\"\", \"address\":\"\", \"website\":\"\", \"confidence_score\":0.0}

        RULES:
        - 'name' is the person's name.
        - 'activity' is the job title.
        - 'website' starts with 'www' or 'http'.
        - If multiple emails are found, join them with '/'. Example: amine@gmail.com/said@gmail.com.
        - If multiple phone numbers are found, join them with '/'. Example: 0675561007/0879224472.
        - If multiple addresses are found, join them with '/'.
        - If any field is not found in the text, use an empty string \"\".
        - Set 'confidence_score' to a float between 0.0 and 1.0 based on your certainty.

        TEXT TO PROCESS:
        ---
        {$this->extractedText}
        ---
        ";

        $apiKey = config('app.mistral_api_key');
        if (! $apiKey) {
            Log::warning('MISTRAL_API_KEY is not set. Falling back to simple local parsing.');

            $structuredData = $this->fallbackExtract($this->extractedText);
            $structuredData['confidence_score'] = 0.0;
            $structuredData['needs_review'] = true;
            $structuredData['status'] = 'validated';

            $this->contact->update($structuredData);

            return;
        }

        $response = Http::withToken($apiKey)
            ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->timeout(60)
            ->post('https://api.mistral.ai/v1/chat/completions', [
                'model' => 'mistral-tiny',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

        if (! $response->successful()) {
            throw new \Exception('Mistral API failed. Status: '.$response->status().' Body: '.$response->body());
        }

        $rawJsonResponse = data_get($response->json(), 'choices.0.message.content');
        $structuredData = is_string($rawJsonResponse) ? json_decode($rawJsonResponse, true) : null;

        if (is_null($structuredData) || ! is_array($structuredData)) {
            throw new \Exception('Failed to decode JSON from Mistral response. Raw: '.(string) $rawJsonResponse);
        }

        $structuredData['email'] = $this->normalizeEmails($structuredData['email'] ?? null);
        $structuredData['phone'] = $this->normalizePhones($structuredData['phone'] ?? null);
        $structuredData['address'] = $this->normalizeAddresses($structuredData['address'] ?? null);

        $confidence = (float) ($structuredData['confidence_score'] ?? 0.5);
        $structuredData['confidence_score'] = $confidence;
        $structuredData['needs_review'] = $confidence < 0.85;
        $structuredData['status'] = 'validated';

        $this->contact->update($structuredData);
        Log::info("Mistral AI structuring successful for contact ID {$this->contact->id}.");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job permanently failed for contact ID {$this->contact->id} after all retries. Error: ".$exception->getMessage());
        $this->contact->update(['status' => 'failed', 'needs_review' => true]);
    }

    private function normalizeEmails(mixed $value): ?string
    {
        $chunks = $this->toArray($value, '/[,;|\s\/]+/');
        $emails = [];

        foreach ($chunks as $chunk) {
            $candidate = mb_strtolower(trim($chunk));
            if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $candidate;
            }
        }

        $emails = array_values(array_unique($emails));

        return empty($emails) ? null : implode('/', $emails);
    }

    private function normalizePhones(mixed $value): ?string
    {
        $chunks = $this->toArray($value, '/\s*(?:\/|,|;|\||\n+)\s*/');
        $phones = [];

        foreach ($chunks as $chunk) {
            $candidate = trim((string) preg_replace('/^(tel|phone|mobile|mob|m)[:\s.-]+/i', '', trim($chunk)));
            if ($candidate !== '') {
                $phones[] = $candidate;
            }
        }

        $phones = array_values(array_unique($phones));

        return empty($phones) ? null : implode('/', $phones);
    }

    private function normalizeAddresses(mixed $value): ?string
    {
        $chunks = $this->toArray($value, '/\s*(?:\/|\||;)\s*/');
        $addresses = [];

        foreach ($chunks as $chunk) {
            $candidate = trim((string) $chunk);
            if ($candidate !== '') {
                $addresses[] = $candidate;
            }
        }

        $addresses = array_values(array_unique($addresses));

        return empty($addresses) ? null : implode('/', $addresses);
    }

    private function toArray(mixed $value, string $pattern): array
    {
        if (is_null($value)) {
            return [];
        }

        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value), fn (string $item): bool => trim($item) !== ''));
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return [];
        }

        $parts = preg_split($pattern, $stringValue) ?: [];

        return array_values(array_filter(array_map('trim', $parts), fn (string $item): bool => $item !== ''));
    }

    private function fallbackExtract(string $text): array
    {
        $email = null;
        $phone = null;
        $website = null;

        if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text, $matches)) {
            $email = mb_strtolower($matches[0]);
        }

        if (preg_match('/(\+?\d[\d\s().-]{7,}\d)/', $text, $matches)) {
            $phone = trim($matches[1]);
        }

        if (preg_match('/(https?:\/\/\S+|www\.\S+)/i', $text, $matches)) {
            $website = rtrim(trim($matches[1]), '.,;)');
        }

        $name = null;
        $company = null;
        $activity = null;

        $lines = array_values(
            array_filter(
                array_map('trim', preg_split('/\R+/', $text) ?: []),
                fn (string $line): bool => $line !== ''
            )
        );

        if (! empty($lines)) {
            $name = $lines[0];
            if (count($lines) > 1) {
                $company = $lines[1];
            }
            if (count($lines) > 2) {
                $activity = $lines[2];
            }
        }

        return [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'company' => $company,
            'activity' => $activity,
            'address' => null,
            'website' => $website,
        ];
    }
}
