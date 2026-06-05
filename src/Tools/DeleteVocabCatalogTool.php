<?php

namespace Platform\Vocab\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Vocab\Models\VocabCatalog;

class DeleteVocabCatalogTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'vocab.catalogs.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /vocab/catalogs/{id} - Löscht einen Katalog. Die enthaltenen VocabLists bleiben erhalten (nur Pivot wird entfernt).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'uuid' => ['type' => 'string'],
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
                return ToolResult::error('ACCESS_DENIED', 'Nur der Owner kann diesen Katalog löschen.');
            }

            $id = $catalog->id;
            $catalog->delete();

            return ToolResult::success([
                'id' => $id,
                'message' => 'Katalog gelöscht. VocabLists bleiben erhalten.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['vocab', 'catalogs', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'destructive',
            'idempotent' => false,
        ];
    }
}
