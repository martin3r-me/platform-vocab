<?php

namespace Platform\Vocab\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Models\Team;
use Platform\Vocab\Models\VocabList;
use Platform\Vocab\Models\VocabEntry;

class AddVocabEntryTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'vocab.entries.POST';
    }

    public function getDescription(): string
    {
        return 'POST /vocab/entries - Fügt eine einzelne Vokabel zu einer Liste hinzu.';
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
                'term' => [
                    'type' => 'string',
                    'description' => 'Wort/Phrase in Zielsprache (ERFORDERLICH).',
                ],
                'translation' => [
                    'type' => 'string',
                    'description' => 'Übersetzung in Ausgangssprache (ERFORDERLICH).',
                ],
                'gender' => [
                    'type' => 'string',
                    'description' => 'Optional: Genus (m, f, n).',
                ],
                'plural' => [
                    'type' => 'string',
                    'description' => 'Optional: Pluralform.',
                ],
                'word_type' => [
                    'type' => 'string',
                    'description' => 'Optional: Wortart (noun, verb, adjective, adverb, phrase, etc.).',
                ],
                'example_sentence' => [
                    'type' => 'string',
                    'description' => 'Optional: Beispielsatz.',
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Optional: Notizen, Eselsbrücken.',
                ],
                'pronunciation' => [
                    'type' => 'string',
                    'description' => 'Optional: Aussprache/Lautschrift.',
                ],
            ],
            'required' => ['list_id', 'term', 'translation'],
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

            $term = trim((string)($arguments['term'] ?? ''));
            if ($term === '') {
                return ToolResult::error('VALIDATION_ERROR', 'term ist erforderlich.');
            }

            $translation = trim((string)($arguments['translation'] ?? ''));
            if ($translation === '') {
                return ToolResult::error('VALIDATION_ERROR', 'translation ist erforderlich.');
            }

            $maxSort = $list->entries()->max('sort_order') ?? 0;

            $entry = VocabEntry::create([
                'vocab_list_id' => $list->id,
                'term' => $term,
                'translation' => $translation,
                'gender' => !empty($arguments['gender']) ? (string)$arguments['gender'] : null,
                'plural' => !empty($arguments['plural']) ? (string)$arguments['plural'] : null,
                'word_type' => !empty($arguments['word_type']) ? (string)$arguments['word_type'] : null,
                'example_sentence' => !empty($arguments['example_sentence']) ? (string)$arguments['example_sentence'] : null,
                'notes' => !empty($arguments['notes']) ? (string)$arguments['notes'] : null,
                'pronunciation' => !empty($arguments['pronunciation']) ? (string)$arguments['pronunciation'] : null,
                'sort_order' => $maxSort + 1,
            ]);

            return ToolResult::success([
                'id' => $entry->id,
                'uuid' => $entry->uuid,
                'term' => $entry->term,
                'translation' => $entry->translation,
                'list_id' => $list->id,
                'message' => 'Vokabel erfolgreich hinzugefügt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Hinzufügen der Vokabel: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['vocab', 'entries', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => false,
        ];
    }
}
