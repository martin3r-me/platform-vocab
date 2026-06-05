<?php

namespace Platform\Vocab\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Models\Team;
use Platform\Vocab\Models\VocabEntry;
use Platform\Vocab\Models\VocabList;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TextToSpeechTool implements ToolContract, ToolMetadataContract
{
    private const VOICES = ['alloy', 'ash', 'ballad', 'coral', 'echo', 'fable', 'onyx', 'nova', 'sage', 'shimmer'];
    private const MODELS = ['tts-1', 'tts-1-hd', 'gpt-4o-mini-tts'];
    private const FORMATS = ['mp3', 'opus', 'aac', 'flac', 'wav', 'pcm'];

    public function getName(): string
    {
        return 'vocab.tts.POST';
    }

    public function getDescription(): string
    {
        return 'POST /vocab/tts - Text-to-Speech: Generiert Audio-Aussprache für einen Text oder eine Vokabel. '
             . 'Nutze entry_id um die Aussprache einer gespeicherten Vokabel zu generieren, oder text für beliebigen Text. '
             . 'Gibt eine Base64-encodierte Audiodatei zurück.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: Team aus Kontext.',
                ],
                'text' => [
                    'type' => 'string',
                    'description' => 'Text zum Vorlesen. Entweder text oder entry_id angeben.',
                ],
                'entry_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID eines Vokabel-Eintrags. Liest den term (Zielsprache) vor. Wenn gesetzt, wird text ignoriert.',
                ],
                'voice' => [
                    'type' => 'string',
                    'description' => 'Stimme (default: "nova"). Werte: alloy, ash, ballad, coral, echo, fable, onyx, nova, sage, shimmer.',
                ],
                'model' => [
                    'type' => 'string',
                    'description' => 'TTS-Modell (default: "gpt-4o-mini-tts"). Werte: tts-1, tts-1-hd, gpt-4o-mini-tts.',
                ],
                'speed' => [
                    'type' => 'number',
                    'description' => 'Geschwindigkeit (default: 1.0). Bereich: 0.25 bis 4.0. Langsamer = besser zum Lernen.',
                ],
                'format' => [
                    'type' => 'string',
                    'description' => 'Audio-Format (default: "mp3"). Werte: mp3, opus, aac, flac, wav, pcm.',
                ],
                'include_example' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Wenn entry_id gesetzt, auch den Beispielsatz vorlesen (default: false).',
                ],
                'instructions' => [
                    'type' => 'string',
                    'description' => 'Optional: Anweisungen für die Stimme, z.B. "Sprich langsam und deutlich" oder "Betone die Vokale". Nur für gpt-4o-mini-tts.',
                ],
            ],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $teamId = $arguments['team_id'] ?? $context->team?->id;
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team angegeben und kein Team im Kontext gefunden.');
            }

            $team = Team::find((int)$teamId);
            if (!$team) {
                return ToolResult::error('TEAM_NOT_FOUND', 'Team nicht gefunden.');
            }

            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }
            $userHasAccess = $context->user->teams()->where('teams.id', $team->id)->exists();
            if (!$userHasAccess) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Team.');
            }

            // Resolve text
            $text = '';
            $entryMeta = null;

            if (!empty($arguments['entry_id'])) {
                $entry = VocabEntry::with('vocabList')->find((int)$arguments['entry_id']);
                if (!$entry || (int)$entry->vocabList->team_id !== (int)$team->id) {
                    return ToolResult::error('NOT_FOUND', 'Vokabel nicht gefunden.');
                }

                $text = $entry->term;
                if (!empty($arguments['include_example']) && $entry->example_sentence) {
                    $text .= '. ' . $entry->example_sentence;
                }

                $entryMeta = [
                    'entry_id' => $entry->id,
                    'term' => $entry->term,
                    'translation' => $entry->translation,
                    'language' => $entry->vocabList->target_language,
                ];
            } elseif (!empty($arguments['text'])) {
                $text = trim((string)$arguments['text']);
            }

            if ($text === '') {
                return ToolResult::error('VALIDATION_ERROR', 'Entweder text oder entry_id muss angegeben werden.');
            }

            if (strlen($text) > 4096) {
                return ToolResult::error('VALIDATION_ERROR', 'Text darf maximal 4096 Zeichen lang sein.');
            }

            // Parameters
            $voice = $arguments['voice'] ?? 'nova';
            if (!in_array($voice, self::VOICES)) {
                $voice = 'nova';
            }

            $model = $arguments['model'] ?? 'gpt-4o-mini-tts';
            if (!in_array($model, self::MODELS)) {
                $model = 'gpt-4o-mini-tts';
            }

            $speed = (float)($arguments['speed'] ?? 1.0);
            $speed = max(0.25, min(4.0, $speed));

            $format = $arguments['format'] ?? 'mp3';
            if (!in_array($format, self::FORMATS)) {
                $format = 'mp3';
            }

            // Build API request
            $apiKey = $this->getApiKey();
            $payload = [
                'model' => $model,
                'input' => $text,
                'voice' => $voice,
                'response_format' => $format,
                'speed' => $speed,
            ];

            // Instructions only supported by gpt-4o-mini-tts
            if ($model === 'gpt-4o-mini-tts' && !empty($arguments['instructions'])) {
                $payload['instructions'] = (string)$arguments['instructions'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post('https://api.openai.com/v1/audio/speech', $payload);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMsg = $errorBody['error']['message'] ?? $response->body();
                return ToolResult::error('TTS_ERROR', 'TTS-API Fehler: ' . $errorMsg);
            }

            $audioContent = $response->body();
            $base64Audio = base64_encode($audioContent);
            $mimeType = match ($format) {
                'mp3' => 'audio/mpeg',
                'opus' => 'audio/opus',
                'aac' => 'audio/aac',
                'flac' => 'audio/flac',
                'wav' => 'audio/wav',
                'pcm' => 'audio/pcm',
                default => 'audio/mpeg',
            };

            $resultData = [
                'audio_base64' => $base64Audio,
                'mime_type' => $mimeType,
                'format' => $format,
                'size_bytes' => strlen($audioContent),
                'text' => $text,
                'voice' => $voice,
                'model' => $model,
                'speed' => $speed,
            ];

            if ($entryMeta) {
                $resultData['entry'] = $entryMeta;
            }

            return ToolResult::success($resultData);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler bei Text-to-Speech: ' . $e->getMessage());
        }
    }

    private function getApiKey(): string
    {
        $key = config('services.openai.api_key');
        if (!is_string($key) || $key === '') {
            $key = config('services.openai.key') ?? '';
        }
        if ($key === '') {
            $key = env('OPENAI_API_KEY') ?? '';
        }
        if ($key === '') {
            throw new \RuntimeException('OPENAI_API_KEY fehlt oder ist leer.');
        }
        return $key;
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['vocab', 'tts', 'audio', 'pronunciation'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
            'side_effects' => ['external_api_call'],
        ];
    }
}
