<?php

namespace App\Providers;

use App\Services\LeadReminder\LeadReminderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('partials.shell-app', function ($view): void {
            $user = Auth::user();

            if ($user === null) {
                $view->with([
                    'reminderInboxCount' => 0,
                    'reminderInboxItems' => collect(),
                ]);

                return;
            }

            $leadReminderService = app(LeadReminderService::class);

            $view->with([
                'reminderInboxCount' => $leadReminderService->operationalInboxCountFor($user),
                'reminderInboxItems' => $leadReminderService->operationalInboxItemsFor($user),
            ]);
        });
    }
}
