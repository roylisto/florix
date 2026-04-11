<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LLMService
{
    /**
     * Send a prompt to the local Ollama instance.
     *
     * @param string $prompt
     * @param callable|null $onProgress
     * @return string
     * @throws \Exception
     */
    public function generate(string $prompt, ?callable $onProgress = null): string
    {
        $url = config('services.ollama.url', 'http://localhost:11434/api/generate');
        $model = config('services.ollama.model', 'mistral');

        if (config('services.ollama.mock', false)) {
            Log::info('LLMService: Using MOCK response');
            return $this->mockResponse();
        }

        try {
            Log::info("LLMService: Starting request to Ollama ({$url}) with model {$model}");
            $startTime = microtime(true);

            $fullResponse = '';
            // Use a 30-minute timeout for the initial connection and the stream
            $response = Http::timeout(1800)
                ->withOptions([
                    'stream' => true,
                    'connect_timeout' => 60, // 1 minute to just connect
                ])
                ->post($url, [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => true,
                    'options' => [
                        'num_predict' => 1500, // Reduced slightly for speed
                        'temperature' => 0.1,  // Keep it deterministic
                        'num_ctx' => 4096,     // Ensure enough context for 50 files
                        'top_k' => 20,         // Faster sampling
                    ]
                ]);

            if ($response->failed()) {
                Log::error('LLMService error: ' . $response->status() . ' - ' . $response->body());
                throw new \Exception('Ollama request failed with status ' . $response->status());
            }

            Log::info("LLMService: Headers received from Ollama. Starting to read stream...");
            $body = $response->toPsrResponse()->getBody();
            $chunkCount = 0;
            $lastProgressUpdate = microtime(true);

            while (!$body->eof()) {
                $line = $this->readLine($body);
                if (empty($line)) continue;

                $data = json_decode($line, true);
                if (isset($data['response'])) {
                    $fullResponse .= $data['response'];
                    $chunkCount++;

                    // Provide progress updates every 20 tokens or every 5 seconds
                    if ($onProgress && ($chunkCount % 20 === 0 || (microtime(true) - $lastProgressUpdate) > 5)) {
                        $onProgress("Received " . $chunkCount . " tokens...");
                        $lastProgressUpdate = microtime(true);
                    }
                    
                    if ($chunkCount === 1 || $chunkCount % 100 === 0) {
                        Log::debug("LLMService: Received {$chunkCount} tokens...");
                    }
                }

                if (isset($data['done']) && $data['done'] === true) {
                    break;
                }
            }

            $duration = round(microtime(true) - $startTime, 2);
            Log::info("LLMService: Completed in {$duration}s. Received " . strlen($fullResponse) . " bytes.");

            if (empty($fullResponse)) {
                Log::error('LLMService error: Empty response from Ollama.');
                throw new \Exception('Empty response from Ollama');
            }

            return $fullResponse;
        } catch (\Exception $e) {
            Log::error('LLMService exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Read a line from a stream.
     */
    protected function readLine($stream): string
    {
        $line = '';
        while (!$stream->eof()) {
            $char = $stream->read(1);
            if ($char === '' || $char === "\n") {
                break;
            }
            $line .= $char;
        }
        return trim($line);
    }

    protected function mockResponse(): string
    {
        return <<<MOCK
FEATURES
- User Dashboard for monitoring system stats
- Authentication system for secure access
- Real-time data updates and filtering

WHAT USER SEES
The user will see a clean dashboard with various statistics and graphs. There is a login page for access control and a settings page to configure the application.

USER FLOW
Step-by-step:
User opens the application → System shows the login page → User enters credentials → System redirects to dashboard → User clicks on a report → System displays detailed statistics.

MERMAID DIAGRAM
graph TD
A[User opens app] --> B[System shows login]
B --> C[User logs in]
C --> D[System shows dashboard]
D --> E[User views reports]
E --> F[System updates charts]
MOCK;
    }
}
