<?php

use Platform\Vocab\Livewire\Catalogs\Index as CatalogsIndex;
use Platform\Vocab\Livewire\Catalogs\Show as CatalogsShow;
use Platform\Vocab\Livewire\Dashboard;
use Platform\Vocab\Livewire\Lists\Index as ListsIndex;
use Platform\Vocab\Livewire\Lists\Show as ListsShow;
use Platform\Vocab\Livewire\Quiz\Play as QuizPlay;
use Platform\Vocab\Livewire\Review;

Route::get('/', Dashboard::class)->name('vocab.dashboard');
Route::get('/catalogs', CatalogsIndex::class)->name('vocab.catalogs.index');
Route::get('/catalogs/{uuid}', CatalogsShow::class)->name('vocab.catalogs.show');
Route::get('/lists', ListsIndex::class)->name('vocab.lists.index');
Route::get('/lists/{uuid}', ListsShow::class)->name('vocab.lists.show');
Route::get('/quiz/{uuid}', QuizPlay::class)->name('vocab.quiz.play');
Route::get('/review', Review::class)->name('vocab.review');
