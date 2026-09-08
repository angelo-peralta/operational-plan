<?php

test('operational plan cards span the available content width', function () {
    $operationalPlansSource = file_get_contents(resource_path('js/pages/operational-plans/index.tsx'));

    expect($operationalPlansSource)
        ->toBeString()
        ->toContain('<div className="grid gap-4">')
        ->not->toContain('<div className="grid gap-4 lg:grid-cols-2">');
});
