<?php

namespace Platform\Vocab\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Models\Team;
use Platform\Vocab\Models\VocabList;

class GetVocabListTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'vocab.lists.{id}.GET';
    }

    public function getDescription(): string
    {
        return 'GET /vocab/lists/{id} - Lädt eine Vokabelliste mit allen Einträgen.';
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
                    'description' => 'ID oder UUID der Liste (ERFORDERLICH). Nutze vocab.lists.GET um Listen zu finden.',
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

            $identifier = $arguments['id'];
            $list = VocabList::where('team_id', $team->id)
                ->where(function ($q) use ($identifier) {
                    $q->where('id', $identifier)->orWhere('uuid', $identifier);
                })
                ->first();

            if (!$list) {
                return ToolResult::error('NOT_FOUND', 'Vokabelliste nicht gefunden.');
            }

            $entries = $list->entries->map(fn ($entry) => [
                'id' => $entry->id,
                'uuid' => $entry->uuid,
                'term' => $entry->term,
                'translation' => $entry->translation,
                'gender' => $entry->gender,
                'plural' => $entry->plural,
                'word_type' => $entry->word_type,
                'example_sentence' => $entry->example_sentence,
                'notes' => $entry->notes,
                'pronunciation' => $entry->pronunciation,
                'sort_order' => $entry->sort_order,
            ])->toArray();

            return ToolResult::success([
                'id' => $list->id,
                'uuid' => $list->uuid,
                'name' => $list->name,
                'description' => $list->description,
                'source_language' => $list->source_language,
                'target_language' => $list->target_language,
                'level' => $list->level,
                'tags' => $list->tags,
                'entries' => $entries,
                'entries_count' => count($entries),
                'team_id' => $team->id,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Liste: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'read',
            'tags' => ['vocab', 'lists', 'detail'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
