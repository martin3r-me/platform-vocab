<?php

use Platform\Vocab\Livewire\Dashboard;
use Platform\Vocab\Livewire\Lists\Index as ListsIndex;
use Platform\Vocab\Livewire\Lists\Show as ListsShow;
use Platform\Vocab\Livewire\Quiz\Play as QuizPlay;

Route::get('/', Dashboard::class)->name('vocab.dashboard');
Route::get('/lists', ListsIndex::class)->name('vocab.lists.index');
Route::get('/lists/{uuid}', ListsShow::class)->name('vocab.lists.show');
Route::get('/quiz/{uuid}', QuizPlay::class)->name('vocab.quiz.play');
