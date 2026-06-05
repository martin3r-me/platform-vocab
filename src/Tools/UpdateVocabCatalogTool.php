<?php

namespace Platform\Vocab\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Vocab\Models\VocabCatalog;

class UpdateVocabCatalogTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'vocab.catalogs.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /vocab/catalogs/{id} - Aktualisiert Catalog-Metadaten (nur Owner).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'uuid' => ['type' => 'string'],
                'name' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'visibility' => ['type' => 'string'],
                'cover_color' => ['type' => 'string'],
                'sort_order' => ['type' => 'integer'],
            ],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user || !$context->team) {
                return ToolResult::error('AUTH_ERROR', 'Auth/Team-Kontext fehlt.');
            }

            $q = VocabCatalog::query()->visibleTo($context->user->id, $context->team->id);
            $catalog = !empty($arguments['uuid'])
                ? $q->where('uuid', $arguments['uuid'])->first()
                : (!empty($arguments['id']) ? $q->find((int)$arguments['id']) : null);

            if (!$catalog) {
                return ToolResult::error('NOT_FOUND', 'Catalog nicht gefunden.');
            }
            if (!$catalog->isOwnedBy($context->user->id)) {
                return ToolResult::error('ACCESS_DENIED', 'Nur der Owner kann diesen Katalog bearbeiten.');
            }

            $updates = [];
            foreach (['name', 'description', 'visibility', 'cover_color', 'sort_order'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $updates[$field] = $arguments[$field];
                }
            }

            if (isset($updates['visibility']) && !in_array($updates['visibility'], ['team', 'personal'], true)) {
                return ToolResult::error('VALIDATION_ERROR', 'visibility muss "team" oder "personal" sein.');
            }

            if (!empty($updates)) {
                $catalog->update($updates);
                $catalog->refresh();
            }

            return ToolResult::success([
                'id' => $catalog->id,
                'uuid' => $catalog->uuid,
                'name' => $catalog->name,
                'visibility' => $catalog->visibility,
                'message' => 'Katalog aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['vocab', 'catalogs', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => false,
        ];
    }
}
