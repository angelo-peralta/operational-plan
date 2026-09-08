<?php

namespace App\Providers;

use App\Models\Accomplishment;
use App\Models\Evaluation;
use App\Models\Evidence;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        Relation::enforceMorphMap([
            'operational_plan' => OperationalPlan::class,
            'key_result_area' => KeyResultArea::class,
            'plan_item' => PlanItem::class,
            'accomplishment' => Accomplishment::class,
            'evidence' => Evidence::class,
            'evaluation' => Evaluation::class,
        ]);

        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
