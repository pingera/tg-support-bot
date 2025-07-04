<?php

namespace App\Logging;

use GuzzleHttp\Client;
use Throwable;

class LokiLogger
{
    protected $client;
    protected $url;
    protected $enabled;

    public function __construct()
    {
        $this->client = new Client();
        $this->url = env('LOKI_URL_PUSH', 'http://loki:3100/loki/api/v1/push');
        
        // Check the environment variable. Default to 'false' if not set.
        $this->enabled = env('LOKI_ENABLED', false);
    }

    /**
     * @param Throwable $e
     * @return void
     */
    public function sendBasicLog(Throwable $e): void
    {
        // Also check if logging is enabled here
        if ($this->enabled == false) {
            return;
        }

        $request = request();

        $errorMessageString = 'File: ' . $e->getFile() . '; ';
        $errorMessageString .= 'Line: ' . $e->getLine() . '; ';
        $errorMessageString .= 'Error: ' . $e->getMessage();

        $this->log('error', $errorMessageString, [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request' => $request->all(),
        ]);
    }

    /**
     * Log a message to the given channel.
     *
     * @param  string  $level
     * @param  mixed  $message
     * @param  array  $context
     * @return void
     */
    public function log(string $level, mixed $message, array $context = []): void
    {
        // THIS IS THE FIX:
        // If LOKI_ENABLED is not 'true', simply return and do nothing.
        if ($this->enabled == false) {
            return;
        }

        try {
            $payload = [
                'streams' => [
                    [
                        'stream' => [
                            'app' => env('APP_NAME', 'Laravel'),
                            'env' => env('APP_ENV', 'production'),
                            'level' => $level,
                        ],
                        'values' => [
                            [
                                (string) (int) (microtime(true) * 1e9),
                                $message,
                            ],
                        ],
                    ],
                ],
            ];

            $this->client->post($this->url, [
                'json' => $payload,
            ]);
        } catch (\Exception $e) {
            // It's good practice to log the logging failure itself,
            // but to the default Laravel logger, not back to Loki.
            \Illuminate\Support\Facades\Log::channel('stack')->error(
                'Failed to send log to Loki: ' . $e->getMessage()
            );
        }
    }
}

