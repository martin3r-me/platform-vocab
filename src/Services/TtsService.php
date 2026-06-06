<?php

namespace Platform\Vocab\Services;

use Illuminate\Support\Facades\Http;

class TtsService
{
    public function synthesize(string $text, ?string $instructions = null, string $voice = 'nova', float $speed = 0.9): ?string
    {
        $apiKey = config('services.openai.api_key')
            ?: config('services.openai.key')
            ?: env('OPENAI_API_KEY');

        if (!$apiKey || trim($text) === '') {
            return null;
        }

        try {
            $payload = [
                'model' => 'gpt-4o-mini-tts',
                'input' => $text,
                'voice' => $voice,
                'response_format' => 'mp3',
                'speed' => $speed,
            ];

            if ($instructions) {
                $payload['instructions'] = $instructions;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(15)
                ->post('https://api.openai.com/v1/audio/speech', $payload);

            if (!$response->successful()) {
                return null;
            }

            return 'data:audio/mpeg;base64,' . base64_encode($response->body());
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function synthesizeTermAndExample(string $term, ?string $example = null): ?string
    {
        $text = $term;
        if ($example) {
            $text .= ' ... ' . $example;
        }

        return $this->synthesize(
            $text,
            'Speak clearly and naturally. First say the word, then pause briefly, then say the example sentence. Pronounce as a native speaker would.'
        );
    }
}
