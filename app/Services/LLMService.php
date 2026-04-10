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
     * @return string
     * @throws \Exception
     */
    public function generate(string $prompt): string
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
            $response = Http::timeout(300)
                ->withOptions(['stream' => true])
                ->post($url, [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => true,
                ]);

            if ($response->failed()) {
                Log::error('LLMService error: ' . $response->status());
                throw new \Exception('Ollama request failed: ' . $response->status());
            }

            Log::info("LLMService: Headers received from Ollama. Starting to read stream...");
            $body = $response->toPsrResponse()->getBody();
            $chunkCount = 0;
            while (!$body->eof()) {
                $line = $this->readLine($body);
                if (empty($line)) continue;

                $data = json_decode($line, true);
                if (isset($data['response'])) {
                    $fullResponse .= $data['response'];
                    $chunkCount++;
                    if ($chunkCount === 1 || $chunkCount % 20 === 0) {
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
