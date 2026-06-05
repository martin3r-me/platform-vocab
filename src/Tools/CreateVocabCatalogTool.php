<?php

namespace Platform\Vocab\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Models\Team;
use Platform\Vocab\Models\VocabCatalog;

class CreateVocabCatalogTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'vocab.catalogs.POST';
    }

    public function getDescription(): string
    {
        return 'POST /vocab/catalogs - Erstellt einen neuen Katalog (thematische Sammlung von VocabLists). visibility: team (default) oder personal.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID. Default: aus Kontext.'],
                'name' => ['type' => 'string', 'description' => 'ERFORDERLICH: Name des Katalogs.'],
                'description' => ['type' => 'string', 'description' => 'Optional: Beschreibung.'],
                'visibility' => ['type' => 'string', 'description' => 'Optional: "team" (default) oder "personal".'],
                'cover_color' => ['type' => 'string', 'description' => 'Optional: Hex-Farbe wie "#8b5cf6".'],
            ],
            'required' => ['name'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $teamId = $arguments['team_id'] ?? $context->team?->id;
            if (!$teamId || !$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Auth/Team-Kontext fehlt.');
            }

            $team = Team::find((int)$teamId);
            if (!$team) {
                return ToolResult::error('TEAM_NOT_FOUND', 'Team nicht gefunden.');
            }
            if (!$context->user->teams()->where('teams.id', $team->id)->exists()) {
                return ToolResult::error('ACCESS_DENIED', 'Kein Zugriff auf Team.');
            }

            $name = trim((string)($arguments['name'] ?? ''));
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $visibility = $arguments['visibility'] ?? VocabCatalog::VISIBILITY_TEAM;
            if (!in_array($visibility, [VocabCatalog::VISIBILITY_TEAM, VocabCatalog::VISIBILITY_PERSONAL], true)) {
                return ToolResult::error('VALIDATION_ERROR', 'visibility muss "team" oder "personal" sein.');
            }

            $catalog = VocabCatalog::create([
                'team_id' => $team->id,
                'created_by_user_id' => $context->user->id,
                'name' => $name,
                'description' => !empty($arguments['description']) ? (string)$arguments['description'] : null,
                'visibility' => $visibility,
                'cover_color' => !empty($arguments['cover_color']) ? (string)$arguments['cover_color'] : null,
            ]);

            return ToolResult::success([
                'id' => $catalog->id,
                'uuid' => $catalog->uuid,
                'name' => $catalog->name,
                'visibility' => $catalog->visibility,
                'team_id' => $catalog->team_id,
                'message' => 'Katalog erfolgreich erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['vocab', 'catalogs', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => false,
        ];
    }
}
