<?php

namespace Platform\Vocab\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Vocab\Models\VocabCatalog;

class GetVocabCatalogTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'vocab.catalogs.{id}.GET';
    }

    public function getDescription(): string
    {
        return 'GET /vocab/catalogs/{id} - Details eines Katalogs inkl. enthaltener Listen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Catalog-ID (alternativ uuid).'],
                'uuid' => ['type' => 'string', 'description' => 'Catalog-UUID (alternativ id).'],
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

            if (!empty($arguments['uuid'])) {
                $catalog = $q->where('uuid', $arguments['uuid'])->first();
            } elseif (!empty($arguments['id'])) {
                $catalog = $q->find((int)$arguments['id']);
            } else {
                return ToolResult::error('VALIDATION_ERROR', 'id oder uuid erforderlich.');
            }

            if (!$catalog) {
                return ToolResult::error('NOT_FOUND', 'Catalog nicht gefunden oder nicht sichtbar.');
            }

            $lists = $catalog->lists()->withCount('entries')->get()->map(fn ($l) => [
                'id' => $l->id,
                'uuid' => $l->uuid,
                'name' => $l->name,
                'source_language' => $l->source_language,
                'target_language' => $l->target_language,
                'level' => $l->level,
                'entries_count' => $l->entries_count,
                'sort_order' => $l->pivot->sort_order,
            ])->toArray();

            return ToolResult::success([
                'id' => $catalog->id,
                'uuid' => $catalog->uuid,
                'name' => $catalog->name,
                'description' => $catalog->description,
                'visibility' => $catalog->visibility,
                'cover_color' => $catalog->cover_color,
                'is_owner' => $catalog->isOwnedBy($context->user->id),
                'lists' => $lists,
                'created_at' => $catalog->created_at?->toIso8601String(),
                'updated_at' => $catalog->updated_at?->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'read',
            'tags' => ['vocab', 'catalogs', 'detail'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
