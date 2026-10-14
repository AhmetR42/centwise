<?php

use Illuminate\Support\Facades\Route;

// The dashboard is a single page that talks to the JSON API under /api/v1.
Route::view('/', 'app');
