<?php

// app/Jobs/ProcessBusinessCard.php

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
    public $backoff = 60; // 60 seconds = 1 minute

    protected $contact;
    protected $extractedText;

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
        // We need to add a check here. If the job has failed before,
        // we don't want to re-log the "Job Started" message and re-update the status to 'failed'
        // if the final attempt also fails. The 'fail' method below handles that.
        if ($this->attempts() > 1) {
            Log::info("Retrying Mistral AI Job for contact ID {$this->contact->id}. Attempt: " . $this->attempts());
        } else {
            Log::info("Mistral AI Job Started for contact ID {$this->contact->id}");
        }

        try {
            Log::info("Received Text for Processing: \n---\n" . $this->extractedText . "\n---");

            $system_prompt = "You are a JSON-only data extraction service. You will be given raw text from a business card. Your only job is to return a single, valid JSON object with the extracted data. Do not output any other text, explanations, or markdown.";
            
            $user_prompt = "From the following text, extract the data into this exact JSON format:
            {\"name\":\"\", \"email\":\"\", \"phone\":\"\", \"company\":\"\", \"activity\":\"\", \"address\":\"\", \"website\":\"\", \"confidence_score\":0.0}

            RULES:
            - 'name' is the person's name.
            - 'activity' is the job title.
            - 'website' starts with 'www' or 'http'.
            - If any field is not found in the text, use an empty string \"\".
            - Set 'confidence_score' to a float between 0.0 and 1.0 based on your certainty.

            TEXT TO PROCESS:
            ---
            {$this->extractedText}
            ---
            ";

            $apiKey = config('app.mistral_api_key' );
            if (!$apiKey) {
                throw new \Exception("MISTRAL_API_KEY is not set.");
            }

            $response = Http::withToken($apiKey)
                ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                ->timeout(60)
                ->post('https://api.mistral.ai/v1/chat/completions', [
                    'model' => 'mistral-tiny',
                    'messages' => [
                        ['role' => 'system', 'content' => $system_prompt],
                        ['role' => 'user', 'content' => $user_prompt]
                    ],
                    'response_format' => ['type' => 'json_object']
                ] );

            if (!$response->successful()) {
                // By throwing an exception here, we tell Laravel the job failed,
                // which will trigger the retry mechanism.
                throw new \Exception("Mistral API failed. Status: " . $response->status() . " Body: " . $response->body());
            }

            $rawJsonResponse = $response->json()['choices'][0]['message']['content'];
            $structuredData = json_decode($rawJsonResponse, true);

            if (is_null($structuredData) || !is_array($structuredData)) {
                throw new \Exception("Failed to decode JSON from Mistral response. Raw: " . $rawJsonResponse);
            }

            $confidence = $structuredData['confidence_score'] ?? 0.5;
            $structuredData['confidence_score'] = $confidence;
            $structuredData['needs_review'] = $confidence < 0.85;
            $structuredData['status'] = 'validated';
            $this->contact->update($structuredData);
            Log::info("Mistral AI structuring successful for contact ID {$this->contact->id}.");

        } catch (\Exception $e) {
            // When an exception is caught, we must re-throw it
            // to let the Laravel queue worker know the job failed and should be retried.
            // The 'fail' method below will be called automatically by Laravel.
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     * This method is called automatically by Laravel after all retries have been exhausted.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        // This is the final failure point.
        // Log the final error and update the contact status to 'failed'.
        Log::error("Job permanently failed for contact ID {$this->contact->id} after all retries. Error: " . $exception->getMessage());
        $this->contact->update(['status' => 'failed', 'needs_review' => true]);
    }
}
