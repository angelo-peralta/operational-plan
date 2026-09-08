<?php

use App\Models\OrganizationalUnit;

test('hierarchy traversal returns descendants once and excludes siblings and self', function () {
    $root = OrganizationalUnit::factory()->university()->create();
    $first = OrganizationalUnit::factory()->for($root, 'parent')->create();
    $second = OrganizationalUnit::factory()->for($root, 'parent')->create();
    $leaf = OrganizationalUnit::factory()->for($first, 'parent')->create();

    expect($root->descendantUnitIds())->toEqualCanonicalizing([$first->id, $second->id, $leaf->id])
        ->and($first->descendantUnitIds())->toBe([$leaf->id])
        ->and($first->isAncestorOf($second))->toBeFalse()
        ->and($root->isAncestorOf($root))->toBeFalse()
        ->and($root->isAncestorOf($leaf))->toBeTrue();
});
