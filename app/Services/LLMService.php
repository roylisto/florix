<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LLMService
{
    protected string $streamBuffer = '';

    /**
     * Send a prompt to the local Ollama instance.
     *
     * @param string $prompt
     * @param callable|null $onProgress
     * @param array $options
     * @return string
     * @throws \Exception
     */
    public function generate(string $prompt, ?callable $onProgress = null, array $options = []): string
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
            $this->streamBuffer = ''; // Reset buffer for each request

            $fullResponse = '';

            // Merge custom options with defaults
            $ollamaOptions = array_merge([
                'num_predict' => 800,  // Default max tokens
                'temperature' => 0.1,  // Keep it deterministic
                'num_ctx' => 2048,     // Context size
                'top_k' => 20,         // Sampling speed
            ], $options);

            // Use a 1-hour timeout for the initial connection and the stream
            $response = Http::timeout(3600)
                ->withOptions([
                    'stream' => true,
                    'connect_timeout' => 60, // 1 minute to just connect
                ])
                ->post($url, [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => true,
                    'options' => $ollamaOptions
                ]);

            if ($response->failed()) {
                Log::error('LLMService error: ' . $response->status() . ' - ' . $response->body());
                throw new \Exception('Ollama request failed with status ' . $response->status());
            }

            Log::info("LLMService: Headers received from Ollama. Starting to read stream...");
            $stream = $response->toPsrResponse()->getBody();
            $chunkCount = 0;
            $lastProgressUpdate = microtime(true);

            // Use a more efficient way to read line-by-line from the stream
            while (!$stream->eof()) {
                $line = $this->readLineFromStream($stream);
                if (empty($line)) continue;

                $data = json_decode($line, true);
                if (isset($data['response'])) {
                    $fullResponse .= $data['response'];
                    $chunkCount++;

                    // Provide progress updates every 20 tokens or every 5 seconds
                    if ($onProgress && ($chunkCount % 20 === 0 || (microtime(true) - $lastProgressUpdate) > 5)) {
                        $onProgress("Generated " . $chunkCount . " tokens...");
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
     * Efficiently read a line from a PSR-7 stream using a buffer.
     */
    protected function readLineFromStream($stream): string
    {
        // If we have a newline in the buffer, return that line
        $pos = strpos($this->streamBuffer, "\n");
        if ($pos !== false) {
            $line = substr($this->streamBuffer, 0, $pos);
            $this->streamBuffer = substr($this->streamBuffer, $pos + 1);
            return trim($line);
        }

        // Otherwise, read more from the stream until we find a newline
        while (!$stream->eof()) {
            $chunk = $stream->read(1024);
            $this->streamBuffer .= $chunk;

            $pos = strpos($this->streamBuffer, "\n");
            if ($pos !== false) {
                $line = substr($this->streamBuffer, 0, $pos);
                $this->streamBuffer = substr($this->streamBuffer, $pos + 1);
                return trim($line);
            }
        }

        // End of stream, return what's left
        $line = $this->streamBuffer;
        $this->streamBuffer = '';
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
