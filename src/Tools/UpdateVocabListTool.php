<?php

namespace Platform\Vocab\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Models\Team;
use Platform\Vocab\Models\VocabList;

class UpdateVocabListTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'vocab.lists.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /vocab/lists/{id} - Aktualisiert eine Vokabelliste (Name, Beschreibung, Level, Tags).';
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
                'id' => [
                    'type' => 'integer',
                    'description' => 'ID der Liste (ERFORDERLICH). Nutze vocab.lists.GET.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Name.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Beschreibung. "" zum Leeren.',
                ],
                'source_language' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Ausgangssprache.',
                ],
                'target_language' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Zielsprache.',
                ],
                'level' => [
                    'type' => 'string',
                    'description' => 'Optional: Neues Niveau. "" zum Leeren.',
                ],
                'tags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Optional: Neue Tags.',
                ],
            ],
            'required' => ['id'],
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

            $list = VocabList::where('team_id', $team->id)->find((int)$arguments['id']);
            if (!$list) {
                return ToolResult::error('NOT_FOUND', 'Vokabelliste nicht gefunden.');
            }

            $update = [];

            if (array_key_exists('name', $arguments)) {
                $name = trim((string)($arguments['name'] ?? ''));
                if ($name === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'name darf nicht leer sein.');
                }
                $update['name'] = $name;
            }

            if (array_key_exists('description', $arguments)) {
                $d = (string)($arguments['description'] ?? '');
                $update['description'] = $d === '' ? null : $d;
            }

            if (array_key_exists('source_language', $arguments)) {
                $sl = trim((string)($arguments['source_language'] ?? ''));
                if ($sl === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'source_language darf nicht leer sein.');
                }
                $update['source_language'] = $sl;
            }

            if (array_key_exists('target_language', $arguments)) {
                $tl = trim((string)($arguments['target_language'] ?? ''));
                if ($tl === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'target_language darf nicht leer sein.');
                }
                $update['target_language'] = $tl;
            }

            if (array_key_exists('level', $arguments)) {
                $l = (string)($arguments['level'] ?? '');
                $update['level'] = $l === '' ? null : $l;
            }

            if (array_key_exists('tags', $arguments)) {
                $update['tags'] = $arguments['tags'];
            }

            if (!empty($update)) {
                $list->update($update);
            }
            $list->refresh();

            return ToolResult::success([
                'id' => $list->id,
                'uuid' => $list->uuid,
                'name' => $list->name,
                'description' => $list->description,
                'source_language' => $list->source_language,
                'target_language' => $list->target_language,
                'level' => $list->level,
                'tags' => $list->tags,
                'message' => 'Vokabelliste erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Liste: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['vocab', 'lists', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => true,
        ];
    }
}
