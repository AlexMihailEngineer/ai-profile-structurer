<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ProfileParserService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.nvidia.key');
        $this->baseUrl = config('services.nvidia.url');
        $this->model = config('services.nvidia.model');
    }

    /**
     * Parse raw LinkedIn text into a structured array.
     */
    public function parse(string $rawText): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(360)
                ->connectTimeout(15) // How long to wait for the initial connection
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "You are a specialized data extraction engine. Your task is to extract professional profile data into a strict JSON format. 
                                          Do not include any conversational text, explanations, or markdown code blocks. 
                                          If a field is missing, return null. 
                                          Dates should be kept as strings exactly as written in the text."
                        ],
                        [
                            'role' => 'user',
                            'content' => $this->buildPrompt($rawText)
                        ]
                    ],
                    'response_format' => ['type' => 'json_object'], // Ensures Kimi respects JSON mode
                    'temperature' => 0.1, // Low temperature for higher accuracy
                ]);

            if ($response->failed()) {
                throw new Exception("NVIDIA NIM API Error: " . $response->body());
            }

            $data = $response->json();

            // Extract the content string and decode it back into a PHP array
            return json_decode($data['choices'][0]['message']['content'], true);
        } catch (Exception $e) {
            Log::error("Profile Parsing Failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Define the strict JSON schema for the LLM.
     */
    protected function buildPrompt(string $text): string
    {
        return <<<PROMPT
Extract the following text into this exact JSON structure:
{
  "name": "string",
  "headline": "string",
  "about": "string",
  "location": "string",
  "skills": ["string", "string"],
  "experiences": [
    {
      "company": "string",
      "title": "string",
      "start_date": "string",
      "end_date": "string",
      "description": "string"
    }
  ]
}

Text to parse:
{$text}
PROMPT;
    }
}
