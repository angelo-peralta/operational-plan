<?php

namespace App\Actions\KeyResultAreaTemplates;

use App\Models\KeyResultAreaTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DeleteKeyResultAreaTemplate
{
    public function handle(User $actor, KeyResultAreaTemplate $template): void
    {
        DB::transaction(function () use ($actor, $template): void {
            KeyResultAreaTemplate::query()->lockForUpdate()->get();
            $lockedTemplate = KeyResultAreaTemplate::query()->findOrFail($template->id);

            Gate::forUser($actor)->authorize('delete', $lockedTemplate);

            if ($lockedTemplate->is_active && KeyResultAreaTemplate::query()->where('is_active', true)->count() === 1) {
                throw ValidationException::withMessages([
                    'template' => __('At least one global KRA template must remain active.'),
                ]);
            }

            $lockedTemplate->delete();
        });
    }
}
