<?php

namespace Platform\Vocab;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class VocabServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vocab.php', 'vocab');
    }

    public function boot(): void
    {
        if (
            config()->has('vocab.routing') &&
            config()->has('vocab.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key'        => 'vocab',
                'title'      => 'Vokabeln',
                'routing'    => config('vocab.routing'),
                'guard'      => config('vocab.guard'),
                'navigation' => config('vocab.navigation'),
                'sidebar'    => config('vocab.sidebar'),
            ]);
        }

        if (PlatformCore::getModule('vocab')) {
            ModuleRouter::group('vocab', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/vocab.php' => config_path('vocab.php'),
        ], 'config');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'vocab');

        $this->registerLivewireComponents();

        $this->registerTools();
    }

    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Vocab\\Livewire';
        $prefix = 'vocab';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }

    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);

            // Vocab Lists CRUD
            $registry->register(new \Platform\Vocab\Tools\ListVocabListsTool());
            $registry->register(new \Platform\Vocab\Tools\GetVocabListTool());
            $registry->register(new \Platform\Vocab\Tools\CreateVocabListTool());
            $registry->register(new \Platform\Vocab\Tools\UpdateVocabListTool());
            $registry->register(new \Platform\Vocab\Tools\DeleteVocabListTool());

            // Vocab Entries CRUD
            $registry->register(new \Platform\Vocab\Tools\AddVocabEntryTool());
            $registry->register(new \Platform\Vocab\Tools\UpdateVocabEntryTool());
            $registry->register(new \Platform\Vocab\Tools\DeleteVocabEntryTool());
            $registry->register(new \Platform\Vocab\Tools\BulkAddVocabEntriesTool());

            // Text-to-Speech
            $registry->register(new \Platform\Vocab\Tools\TextToSpeechTool());
        } catch (\Throwable $e) {
            // ToolRegistry not available yet (e.g. during migrations)
        }
    }
}
