<?php

namespace Platform\Vocab\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Models\Team;
use Platform\Vocab\Models\VocabList;
use Platform\Vocab\Models\VocabEntry;

class BulkAddVocabEntriesTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'vocab.entries.bulk.POST';
    }

    public function getDescription(): string
    {
        return 'POST /vocab/entries/bulk - Fügt mehrere Vokabeln auf einmal zu einer Liste hinzu.';
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
                'list_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Vokabelliste (ERFORDERLICH).',
                ],
                'entries' => [
                    'type' => 'array',
                    'description' => 'Array von Vokabeln (ERFORDERLICH). Jeder Eintrag: { term, translation, gender?, plural?, word_type?, example_sentence?, notes?, pronunciation? }',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'term' => ['type' => 'string'],
                            'translation' => ['type' => 'string'],
                            'gender' => ['type' => 'string'],
                            'plural' => ['type' => 'string'],
                            'word_type' => ['type' => 'string'],
                            'example_sentence' => ['type' => 'string'],
                            'notes' => ['type' => 'string'],
                            'pronunciation' => ['type' => 'string'],
                        ],
                        'required' => ['term', 'translation'],
                    ],
                ],
            ],
            'required' => ['list_id', 'entries'],
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

            $list = VocabList::where('team_id', $team->id)->find((int)$arguments['list_id']);
            if (!$list) {
                return ToolResult::error('NOT_FOUND', 'Vokabelliste nicht gefunden.');
            }

            $entries = $arguments['entries'] ?? [];
            if (empty($entries) || !is_array($entries)) {
                return ToolResult::error('VALIDATION_ERROR', 'entries muss ein nicht-leeres Array sein.');
            }

            if (count($entries) > 100) {
                return ToolResult::error('VALIDATION_ERROR', 'Maximal 100 Einträge pro Bulk-Operation.');
            }

            $maxSort = $list->entries()->max('sort_order') ?? 0;
            $created = [];

            foreach ($entries as $i => $data) {
                $term = trim((string)($data['term'] ?? ''));
                $translation = trim((string)($data['translation'] ?? ''));

                if ($term === '' || $translation === '') {
                    continue;
                }

                $maxSort++;
                $entry = VocabEntry::create([
                    'vocab_list_id' => $list->id,
                    'term' => $term,
                    'translation' => $translation,
                    'gender' => !empty($data['gender']) ? (string)$data['gender'] : null,
                    'plural' => !empty($data['plural']) ? (string)$data['plural'] : null,
                    'word_type' => !empty($data['word_type']) ? (string)$data['word_type'] : null,
                    'example_sentence' => !empty($data['example_sentence']) ? (string)$data['example_sentence'] : null,
                    'notes' => !empty($data['notes']) ? (string)$data['notes'] : null,
                    'pronunciation' => !empty($data['pronunciation']) ? (string)$data['pronunciation'] : null,
                    'sort_order' => $maxSort,
                ]);

                $created[] = [
                    'id' => $entry->id,
                    'uuid' => $entry->uuid,
                    'term' => $entry->term,
                    'translation' => $entry->translation,
                ];
            }

            return ToolResult::success([
                'list_id' => $list->id,
                'created_count' => count($created),
                'entries' => $created,
                'message' => count($created) . ' Vokabeln erfolgreich hinzugefügt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Hinzufügen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['vocab', 'entries', 'bulk', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => false,
        ];
    }
}
