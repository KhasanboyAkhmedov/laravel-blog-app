<?php

namespace App\Providers;

use App\Models\Post;
use App\Observers\PostObserver;
use App\Services\ActivityLoggerService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // ── Post events (created / updated / deleted) ─────────────────────────
        Post::observe(PostObserver::class);

        // ── Successful login ──────────────────────────────────────────────────
        Event::listen(Login::class, function (Login $event) {
            ActivityLoggerService::logRaw(
                action:  'login',
                model:   get_class($event->user),
                modelId: $event->user->id,
                payload: [
                    'email' => $event->user->email,
                    'ip'    => Request::ip(),
                ],
                userId: $event->user->id,
            );
        });

        // ── Logout ────────────────────────────────────────────────────────────
        Event::listen(Logout::class, function (Logout $event) {
            if (! $event->user) return;

            ActivityLoggerService::logRaw(
                action:  'logout',
                model:   get_class($event->user),
                modelId: $event->user->id,
                payload: ['email' => $event->user->email],
                userId:  $event->user->id,
            );
        });

        // ── Failed login attempt ───────────────────────────────────────────────
        Event::listen(Failed::class, function (Failed $event) {
            ActivityLoggerService::logRaw(
                action:  'login_failed',
                model:   'App\\Models\\User',
                modelId: 0,
                payload: [
                    'email' => $event->credentials['email'] ?? null,
                    'ip'    => Request::ip(),
                ],
                userId:  null, // unknown user
            );
        });

        // ── Password reset ────────────────────────────────────────────────────
        Event::listen(PasswordReset::class, function (PasswordReset $event) {
            ActivityLoggerService::logRaw(
                action:  'password_reset',
                model:   get_class($event->user),
                modelId: $event->user->id,
                payload: ['email' => $event->user->email],
                userId:  $event->user->id,
            );
        });
    }
}
