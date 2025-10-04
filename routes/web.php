<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redirect;

Route::fallback(fn() => Redirect::away('https://www.google.com'))->name('login');
