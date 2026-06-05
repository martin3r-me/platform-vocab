<?php

namespace Platform\Vocab\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Models\Team;
use Platform\Vocab\Models\VocabEntry;

class UpdateVocabEntryTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'vocab.entries.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /vocab/entries/{id} - Aktualisiert eine Vokabel.';
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
                    'description' => 'ID des Eintrags (ERFORDERLICH).',
                ],
                'term' => [
                    'type' => 'string',
                    'description' => 'Optional: Neues Wort/Phrase.',
                ],
                'translation' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Übersetzung.',
                ],
                'gender' => [
                    'type' => 'string',
                    'description' => 'Optional: Genus. "" zum Leeren.',
                ],
                'plural' => [
                    'type' => 'string',
                    'description' => 'Optional: Pluralform. "" zum Leeren.',
                ],
                'word_type' => [
                    'type' => 'string',
                    'description' => 'Optional: Wortart. "" zum Leeren.',
                ],
                'example_sentence' => [
                    'type' => 'string',
                    'description' => 'Optional: Beispielsatz. "" zum Leeren.',
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Optional: Notizen. "" zum Leeren.',
                ],
                'pronunciation' => [
                    'type' => 'string',
                    'description' => 'Optional: Aussprache. "" zum Leeren.',
                ],
                'sort_order' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neue Reihenfolge.',
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

            $team = \Platform\Core\Models\Team::find((int)$teamId);
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

            $entry = VocabEntry::with('vocabList')->find((int)$arguments['id']);
            if (!$entry || (int)$entry->vocabList->team_id !== (int)$team->id) {
                return ToolResult::error('NOT_FOUND', 'Vokabel nicht gefunden.');
            }

            $update = [];

            foreach (['term', 'translation'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $val = trim((string)($arguments[$field] ?? ''));
                    if ($val === '') {
                        return ToolResult::error('VALIDATION_ERROR', "{$field} darf nicht leer sein.");
                    }
                    $update[$field] = $val;
                }
            }

            foreach (['gender', 'plural', 'word_type', 'example_sentence', 'notes', 'pronunciation'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $val = (string)($arguments[$field] ?? '');
                    $update[$field] = $val === '' ? null : $val;
                }
            }

            if (array_key_exists('sort_order', $arguments)) {
                $update['sort_order'] = (int)$arguments['sort_order'];
            }

            if (!empty($update)) {
                $entry->update($update);
            }
            $entry->refresh();

            return ToolResult::success([
                'id' => $entry->id,
                'uuid' => $entry->uuid,
                'term' => $entry->term,
                'translation' => $entry->translation,
                'gender' => $entry->gender,
                'plural' => $entry->plural,
                'word_type' => $entry->word_type,
                'message' => 'Vokabel erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Vokabel: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['vocab', 'entries', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => true,
        ];
    }
}
