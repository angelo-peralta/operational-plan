<?php

namespace App\Http\Controllers;

use App\Models\KeyResultAreaTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class KeyResultAreaManagementController extends Controller
{
    public function __invoke(string $currentTeam, Request $request): Response
    {
        Gate::authorize('viewAny', KeyResultAreaTemplate::class);

        return Inertia::render('administration/key-results/index', [
            'templates' => KeyResultAreaTemplate::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (KeyResultAreaTemplate $template): array => [
                    'id' => $template->id,
                    'code' => $template->code,
                    'name' => $template->name,
                    'description' => $template->description,
                    'sortOrder' => $template->sort_order,
                    'isActive' => $template->is_active,
                ]),
        ]);
    }
}
