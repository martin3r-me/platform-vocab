<?php

namespace Platform\Vocab\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Models\Team;
use Platform\Vocab\Models\VocabCatalog;

class ListVocabCatalogsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'vocab.catalogs.GET';
    }

    public function getDescription(): string
    {
        return 'GET /vocab/catalogs - Listet sichtbare Kataloge (Team + persönliche des Users). Catalogs gruppieren VocabLists thematisch.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID. Default: aus Kontext.'],
                'visibility' => ['type' => 'string', 'description' => 'Optional: Filter "team" oder "personal".'],
                'search' => ['type' => 'string', 'description' => 'Optional: Volltextsuche in Name/Beschreibung.'],
                'limit' => ['type' => 'integer', 'description' => 'Max. Ergebnisse (default 50, max 200).'],
                'offset' => ['type' => 'integer'],
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
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $team = Team::find((int)$teamId);
            if (!$team) {
                return ToolResult::error('TEAM_NOT_FOUND', 'Team nicht gefunden.');
            }
            if (!$context->user->teams()->where('teams.id', $team->id)->exists()) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Team.');
            }

            $q = VocabCatalog::query()
                ->visibleTo($context->user->id, $team->id)
                ->withCount('lists');

            if (!empty($arguments['visibility'])) {
                $q->where('visibility', $arguments['visibility']);
            }
            if (!empty($arguments['search'])) {
                $s = $arguments['search'];
                $q->where(function ($q2) use ($s) {
                    $q2->where('name', 'like', "%{$s}%")
                       ->orWhere('description', 'like', "%{$s}%");
                });
            }

            $limit = min((int)($arguments['limit'] ?? 50), 200);
            $offset = (int)($arguments['offset'] ?? 0);
            $total = (clone $q)->count();

            $catalogs = $q->orderBy('sort_order')->orderBy('name')
                ->limit($limit)->offset($offset)->get();

            $items = $catalogs->map(fn ($c) => [
                'id' => $c->id,
                'uuid' => $c->uuid,
                'name' => $c->name,
                'description' => $c->description,
                'visibility' => $c->visibility,
                'cover_color' => $c->cover_color,
                'lists_count' => $c->lists_count,
                'is_owner' => $c->isOwnedBy($context->user->id),
                'created_at' => $c->created_at?->toIso8601String(),
                'updated_at' => $c->updated_at?->toIso8601String(),
            ])->toArray();

            return ToolResult::success([
                'data' => $items,
                'pagination' => ['total' => $total, 'limit' => $limit, 'offset' => $offset],
                'team_id' => $team->id,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'read',
            'tags' => ['vocab', 'catalogs', 'lookup'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
