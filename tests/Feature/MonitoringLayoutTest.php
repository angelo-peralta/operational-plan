<?php

test('monitoring uses the default application layout only once', function () {
    $appSource = file_get_contents(resource_path('js/app.tsx'));
    $monitoringSource = file_get_contents(resource_path('js/pages/monitoring/index.tsx'));

    expect($appSource)
        ->toBeString()
        ->toContain('return AppLayout;')
        ->and($monitoringSource)
        ->toBeString()
        ->not->toContain("import AppLayout from '@/layouts/app-layout';")
        ->not->toContain('<AppLayout');
});
