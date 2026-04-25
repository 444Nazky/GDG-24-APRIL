<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\ChessBoard;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/chess', ChessBoard::class);
