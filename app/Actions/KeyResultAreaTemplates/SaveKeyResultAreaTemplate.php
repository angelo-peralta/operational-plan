<?php

namespace App\Actions\KeyResultAreaTemplates;

use App\Models\KeyResultAreaTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SaveKeyResultAreaTemplate
{
    /** @param array{code: string|null, name: string, description: string|null, sort_order: int, is_active: bool} $data */
    public function handle(User $actor, array $data, ?KeyResultAreaTemplate $template = null): KeyResultAreaTemplate
    {
        return DB::transaction(function () use ($actor, $data, $template): KeyResultAreaTemplate {
            KeyResultAreaTemplate::query()->lockForUpdate()->get();

            $target = $template === null
                ? new KeyResultAreaTemplate
                : KeyResultAreaTemplate::query()->findOrFail($template->id);

            Gate::forUser($actor)->authorize(
                $template === null ? 'create' : 'update',
                $template === null ? KeyResultAreaTemplate::class : $target,
            );

            if ($target->exists && $target->is_active && ! $data['is_active'] && KeyResultAreaTemplate::query()->where('is_active', true)->count() === 1) {
                throw ValidationException::withMessages([
                    'is_active' => __('At least one global KRA template must remain active.'),
                ]);
            }

            $target->fill([
                ...$data,
                'created_by' => $target->exists ? $target->created_by : $actor->id,
                'updated_by' => $actor->id,
            ])->save();

            return $target->refresh();
        });
    }
}
