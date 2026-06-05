<?php

namespace Platform\Vocab\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Models\Team;
use Platform\Vocab\Models\VocabList;

class ListVocabListsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'vocab.lists.GET';
    }

    public function getDescription(): string
    {
        return 'GET /vocab/lists - Listet Vokabellisten. Filter nach Sprache, Level, Tags möglich.';
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
                'source_language' => [
                    'type' => 'string',
                    'description' => 'Optional: Filter nach Ausgangssprache (ISO-Code, z.B. "de").',
                ],
                'target_language' => [
                    'type' => 'string',
                    'description' => 'Optional: Filter nach Zielsprache (ISO-Code, z.B. "it").',
                ],
                'level' => [
                    'type' => 'string',
                    'description' => 'Optional: Filter nach Niveau (A1, A2, B1, B2, C1, C2).',
                ],
                'search' => [
                    'type' => 'string',
                    'description' => 'Optional: Volltextsuche in Name und Beschreibung.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Max. Ergebnisse (default 50, max 200).',
                ],
                'offset' => [
                    'type' => 'integer',
                    'description' => 'Offset für Pagination.',
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

            $q = VocabList::query()->where('team_id', $team->id);

            if (!empty($arguments['source_language'])) {
                $q->where('source_language', $arguments['source_language']);
            }
            if (!empty($arguments['target_language'])) {
                $q->where('target_language', $arguments['target_language']);
            }
            if (!empty($arguments['level'])) {
                $q->where('level', $arguments['level']);
            }
            if (!empty($arguments['search'])) {
                $search = $arguments['search'];
                $q->where(function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                       ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $q->orderBy('updated_at', 'desc');

            $limit = min((int)($arguments['limit'] ?? 50), 200);
            $offset = (int)($arguments['offset'] ?? 0);
            $total = $q->count();

            $lists = $q->limit($limit)->offset($offset)->get();

            $items = $lists->map(fn ($list) => [
                'id' => $list->id,
                'uuid' => $list->uuid,
                'name' => $list->name,
                'description' => $list->description,
                'source_language' => $list->source_language,
                'target_language' => $list->target_language,
                'level' => $list->level,
                'tags' => $list->tags,
                'entries_count' => $list->entries()->count(),
                'created_at' => $list->created_at?->toIso8601String(),
                'updated_at' => $list->updated_at?->toIso8601String(),
            ])->toArray();

            return ToolResult::success([
                'data' => $items,
                'pagination' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                ],
                'team_id' => $team->id,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Listen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'read',
            'tags' => ['vocab', 'lists', 'lookup'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
