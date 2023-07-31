<?php

use Webman\Route;
use support\Request;

use isszz\captcha\Controller;

Route::post('/scaptcha/check', [Controller::class, 'check'])->name('scaptcha.check');
Route::get('/scaptcha/svg[/{path:.+}]', [Controller::class, 'svg'])->name('scaptcha.svg');
Route::get('/scaptcha[/{path:(?!check).+}]', [Controller::class, 'index'])->name('scaptcha.index');
// Route::get('/scaptcha[/{path:.+}]', [Controller::class, 'index'])->name('scaptcha.index');
