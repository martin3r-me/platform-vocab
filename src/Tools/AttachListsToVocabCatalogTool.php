<?php

namespace Platform\Vocab\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Vocab\Models\VocabCatalog;
use Platform\Vocab\Models\VocabList;

class AttachListsToVocabCatalogTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'vocab.catalogs.attach_lists.POST';
    }

    public function getDescription(): string
    {
        return 'POST /vocab/catalogs/{id}/lists - Hängt eine oder mehrere VocabLists in einen Katalog. Idempotent (bestehende Verknüpfungen bleiben).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'catalog_id' => ['type' => 'integer', 'description' => 'Catalog-ID (alternativ catalog_uuid).'],
                'catalog_uuid' => ['type' => 'string', 'description' => 'Catalog-UUID (alternativ catalog_id).'],
                'list_ids' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'ERFORDERLICH: Array von VocabList-IDs.',
                ],
            ],
            'required' => ['list_ids'],
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
                return ToolResult::error('ACCESS_DENIED', 'Nur der Owner kann Listen anhängen.');
            }

            $listIds = array_filter(array_map('intval', (array)($arguments['list_ids'] ?? [])));
            if (empty($listIds)) {
                return ToolResult::error('VALIDATION_ERROR', 'list_ids darf nicht leer sein.');
            }

            $valid = VocabList::where('team_id', $context->team->id)
                ->whereIn('id', $listIds)
                ->pluck('id')
                ->toArray();

            if (empty($valid)) {
                return ToolResult::error('VALIDATION_ERROR', 'Keine sichtbaren VocabLists in list_ids gefunden.');
            }

            $maxSort = (int) $catalog->lists()->max('vocab_catalog_list.sort_order');

            $sync = [];
            foreach ($valid as $id) {
                $maxSort++;
                $sync[$id] = ['sort_order' => $maxSort];
            }

            $catalog->lists()->syncWithoutDetaching($sync);

            return ToolResult::success([
                'catalog_id' => $catalog->id,
                'catalog_uuid' => $catalog->uuid,
                'attached_list_ids' => array_values($valid),
                'skipped_ids' => array_values(array_diff($listIds, $valid)),
                'message' => count($valid) . ' Liste(n) angehängt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['vocab', 'catalogs', 'attach'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => true,
        ];
    }
}
