<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\UsuarioController;

// Auth

Route::get('/login', [AuthController::class, 'showLogin'])->name('login'); // 👈 nombre exacto: login
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
//Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ⬇️ Redirige la raíz al login
Route::redirect('/', '/login');

// Dashboards + CRUD protegidos por rol
Route::middleware(['auth', 'role:administrador'])->group(function () {
    Route::get('/admin', [\App\Http\Controllers\Admin\HomeController::class, 'index'])->name('admin.dashboard');

    Route::resource('usuarios', \App\Http\Controllers\Admin\UsuarioController::class);
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('admin.usuarios.index');
    Route::get('/usuarios/create', [UsuarioController::class, 'create'])->name('admin.usuarios.create');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('admin.usuarios.store');
    Route::get('/usuarios/{usuario}/edit', [UsuarioController::class, 'edit'])->name('admin.usuarios.edit');
    Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('admin.usuarios.update');
    Route::delete('/usuarios/{usuario}', [UsuarioController::class, 'destroy'])->name('admin.usuarios.destroy');
});

Route::middleware(['auth', 'role:docente'])->group(function () {
    Route::get('/docente', [\App\Http\Controllers\Docente\HomeController::class, 'index'])->name('docente.dashboard');
    // aquí irán rutas exclusivas del docente
});

Route::middleware(['web', 'auth', 'role:coordinador'])
    ->get('/coordinador', [\App\Http\Controllers\Coordinador\HomeController::class, 'index'])
    ->name('coordinador.dashboard');


// Todo lo de cuenta disponible para cualquier usuario autenticado
Route::middleware('auth')->group(function () {
    Route::put('/account/profile',  [AccountController::class, 'updateProfile'])->name('account.update.profile');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.update.password');
    Route::put('/account/phone',    [AccountController::class, 'updatePhone'])->name('account.update.phone');
});


use App\Http\Controllers\Coordinador\AulaController;
use App\Http\Controllers\Coordinador\GrupoController;
use App\Http\Controllers\Coordinador\MateriaController;

// --- COORDINADOR ---
Route::middleware(['auth', 'role:coordinador'])
    ->prefix('coordinador')->as('coordinador.')
    ->group(function () {
        // Dashboard
        Route::get('/', [\App\Http\Controllers\Coordinador\HomeController::class, 'index'])
            ->name('dashboard');

        // AULAS (si quieres protegerlo por permiso, descomenta la línea del group)
        // Route::middleware('permiso:gestionar-aulas')->group(function () {
        Route::resource('aulas', AulaController::class)
            ->only(['index', 'create', 'store', 'edit', 'update'])
            ->names('aulas'); // -> genera coordinador.aulas.index, .create, etc.

        Route::put('aulas/{aula}/activar',    [AulaController::class, 'activar'])->name('aulas.activar');
        Route::put('aulas/{aula}/desactivar', [AulaController::class, 'desactivar'])->name('aulas.desactivar');
        // });

        // GRUPOS (protegido por permiso)
        Route::middleware('permiso:gestionar-grupos')->group(function () {
            Route::resource('grupos', GrupoController::class)->except(['show']);
        });

        Route::resource('materias', MateriaController::class)->except(['show']);
        Route::get('materias/asignar', [MateriaController::class, 'assignGroup'])->name('materias.assignGroup');
        Route::post('materias/asignar', [MateriaController::class, 'storeGroupAssignment'])->name('materias.storeGroupAssignment');
        // Toggle del estado en la pivote (materia <-> grupo)
        Route::patch(
            'materias/{materia:sigla}/grupos/{grupo}/toggle',
            [\App\Http\Controllers\Coordinador\MateriaController::class, 'toggleGroup']
        )->name('materias.grupos.toggle');

        // Quitar la asignación materia <-> grupo (opcional)
        Route::delete(
            'materias/{materia:sigla}/grupos/{grupo}',
            [\App\Http\Controllers\Coordinador\MateriaController::class, 'detachGroup']
        )->name('materias.grupos.detach');
    });
