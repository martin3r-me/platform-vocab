<?php

namespace Platform\Vocab\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Models\Team;
use Platform\Vocab\Models\VocabList;

class CreateVocabListTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'vocab.lists.POST';
    }

    public function getDescription(): string
    {
        return 'POST /vocab/lists - Erstellt eine neue Vokabelliste. Sprachen als ISO-Codes (z.B. "de", "it", "en", "fr", "es").';
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
                'name' => [
                    'type' => 'string',
                    'description' => 'Name der Liste (ERFORDERLICH, z.B. "Italienisch Essen & Trinken").',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung/Kontext der Liste.',
                ],
                'source_language' => [
                    'type' => 'string',
                    'description' => 'Ausgangssprache als ISO-Code (ERFORDERLICH, z.B. "de").',
                ],
                'target_language' => [
                    'type' => 'string',
                    'description' => 'Zielsprache als ISO-Code (ERFORDERLICH, z.B. "it").',
                ],
                'level' => [
                    'type' => 'string',
                    'description' => 'Optional: Sprachniveau (A1, A2, B1, B2, C1, C2).',
                ],
                'tags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Optional: Freie Tags als Array.',
                ],
            ],
            'required' => ['name', 'source_language', 'target_language'],
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

            $name = trim((string)($arguments['name'] ?? ''));
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $sourceLang = trim((string)($arguments['source_language'] ?? ''));
            if ($sourceLang === '') {
                return ToolResult::error('VALIDATION_ERROR', 'source_language ist erforderlich.');
            }

            $targetLang = trim((string)($arguments['target_language'] ?? ''));
            if ($targetLang === '') {
                return ToolResult::error('VALIDATION_ERROR', 'target_language ist erforderlich.');
            }

            $list = VocabList::create([
                'team_id' => $team->id,
                'name' => $name,
                'description' => !empty($arguments['description']) ? (string)$arguments['description'] : null,
                'source_language' => $sourceLang,
                'target_language' => $targetLang,
                'level' => !empty($arguments['level']) ? (string)$arguments['level'] : null,
                'tags' => $arguments['tags'] ?? null,
            ]);

            return ToolResult::success([
                'id' => $list->id,
                'uuid' => $list->uuid,
                'name' => $list->name,
                'source_language' => $list->source_language,
                'target_language' => $list->target_language,
                'level' => $list->level,
                'team_id' => $list->team_id,
                'message' => 'Vokabelliste erfolgreich erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Liste: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['vocab', 'lists', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => false,
        ];
    }
}
