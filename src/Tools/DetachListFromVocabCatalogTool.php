<?php

namespace Platform\Vocab\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Vocab\Models\VocabCatalog;

class DetachListFromVocabCatalogTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'vocab.catalogs.detach_list.POST';
    }

    public function getDescription(): string
    {
        return 'POST /vocab/catalogs/{id}/lists/detach - Löst eine VocabList aus einem Katalog. Die Liste selbst bleibt erhalten.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'catalog_id' => ['type' => 'integer'],
                'catalog_uuid' => ['type' => 'string'],
                'list_id' => ['type' => 'integer', 'description' => 'ERFORDERLICH: VocabList-ID, die entfernt wird.'],
            ],
            'required' => ['list_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user || !$context->team) {
                return ToolResult::error('AUTH_ERROR', 'Auth/Team-Kontext fehlt.');
            }

            $q = VocabCatalog::query()->visibleTo($context->user->id, $context->team->id);
            $catalog = !empty($arguments['catalog_uuid'])
                ? $q->where('uuid', $arguments['catalog_uuid'])->first()
                : (!empty($arguments['catalog_id']) ? $q->find((int)$arguments['catalog_id']) : null);

            if (!$catalog) {
                return ToolResult::error('NOT_FOUND', 'Catalog nicht gefunden.');
            }
            if (!$catalog->isOwnedBy($context->user->id)) {
                return ToolResult::error('ACCESS_DENIED', 'Nur der Owner kann Listen entfernen.');
            }

            $listId = (int)($arguments['list_id'] ?? 0);
            if ($listId === 0) {
                return ToolResult::error('VALIDATION_ERROR', 'list_id erforderlich.');
            }

            $detached = $catalog->lists()->detach($listId);

            return ToolResult::success([
                'catalog_id' => $catalog->id,
                'list_id' => $listId,
                'detached' => $detached,
                'message' => $detached ? 'Liste entfernt.' : 'Liste war nicht im Katalog.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['vocab', 'catalogs', 'detach'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => true,
        ];
    }
}
