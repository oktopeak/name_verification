<?php

namespace App\Generator;

use App\Storage\NameStorage;
use GuzzleHttp\Client;

class NameGenerator
{
    private NameStorage $storage;
    private ?string $apiKey;
    private bool $useLLM;

    public function __construct(NameStorage $storage, ?string $apiKey = null)
    {
        $this->storage = $storage;
        $this->apiKey = $apiKey;
        $this->useLLM = !empty($apiKey);
    }

    public function generate(string $prompt): string
    {
        if ($this->useLLM) {
            $name = $this->generateWithLLM($prompt);
        } else {
            $name = $this->generateDeterministic($prompt);
        }

        // Store the latest generated name
        $this->storage->save($name);

        return $name;
    }

    private function generateWithLLM(string $prompt): string
    {
        // Check if Guzzle is available
        if (!class_exists('GuzzleHttp\Client')) {
            // Fallback to deterministic if Guzzle is not available
            return $this->generateDeterministic($prompt);
        }

        // For OpenAI API
        $client = new Client();

        try {
            $response = $client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a name generator. Generate exactly ONE name based on the user\'s prompt. Return ONLY the name, nothing else.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 50
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return trim($data['choices'][0]['message']['content']);
        } catch (\Exception $e) {
            // Fallback to deterministic generation if API fails
            return $this->generateDeterministic($prompt);
        }
    }

    private function generateDeterministic(string $prompt): string
    {
        // Parse common patterns from prompts
        $prompt_lower = strtolower($prompt);

        // Arabic names
        if (strpos($prompt_lower, 'arabic') !== false) {
            $firstNames = ['Mohammed', 'Abdullah', 'Omar', 'Ali', 'Hassan', 'Ahmed', 'Yusuf', 'Ibrahim'];
            $patterns = [];

            if (strpos($prompt_lower, 'al ') !== false || strpos($prompt_lower, 'al-') !== false) {
                $patterns[] = 'Al';
            }
            if (strpos($prompt_lower, 'ibn') !== false) {
                $patterns[] = 'ibn';
            }

            $name = $firstNames[array_rand($firstNames)];

            if (in_array('ibn', $patterns)) {
                $name .= ' ibn ' . $firstNames[array_rand($firstNames)];
            }
            if (in_array('Al', $patterns)) {
                $surnames = ['Rashid', 'Saud', 'Hassan', 'Qasim', 'Fayed', 'Khattab', 'Rahman'];
                $name .= ' Al ' . $surnames[array_rand($surnames)];
            }

            return $name;
        }

        // European/Western names
        if (strpos($prompt_lower, 'european') !== false || strpos($prompt_lower, 'western') !== false) {
            $firstNames = ['John', 'Michael', 'Christopher', 'Alexander', 'Jonathan', 'Steven', 'Elizabeth'];
            $lastNames = ['Smith', 'Johnson', 'Turner', 'McDonald', 'Thompson', 'Nolan', 'Carter'];
            return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
        }

        // Slavic names
        if (strpos($prompt_lower, 'slavic') !== false || strpos($prompt_lower, 'russian') !== false) {
            $firstNames = ['Ivan', 'Mikhail', 'Alexander', 'Dargulov'];
            $lastNames = ['Petrov', 'Gorbachov'];
            return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
        }

        // Default random names
        $firstNames = ['Tyler', 'Bob', 'Sarah', 'Jean-Luc', 'Katherine', 'Sean', 'Emanuel', 'Samantha'];
        $lastNames = ['Bliha', 'Ellensworth', "O'Connor", 'Picard', 'McDonald', "O'Brien", 'Oscar', 'Lee'];

        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }

    public function getLatest(): ?string
    {
        return $this->storage->getLatest();
    }
}