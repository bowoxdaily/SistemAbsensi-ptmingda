<?php

use App\Models\LandingGallery;

uses(Tests\TestCase::class);

it('formats image url correctly for local and remote sources', function () {
    $remote = new LandingGallery([
        'image_path' => 'https://images.unsplash.com/photo-example',
    ]);
    expect($remote->image_url)->toBe('https://images.unsplash.com/photo-example');

    $local = new LandingGallery([
        'image_path' => 'landing_galleries/sample.jpg',
    ]);
    expect($local->image_url)->toContain('storage/landing_galleries/sample.jpg');

    $empty = new LandingGallery([
        'image_path' => '',
    ]);
    expect($empty->image_url)->toContain('sneat-1.0.0/assets/img/elements/18.jpg');
});

it('returns registered categories dictionary with indonesian labels', function () {
    $categories = LandingGallery::categories();

    expect($categories)->toBeArray();
    expect($categories)->toHaveKey('production');
    expect($categories)->toHaveKey('facility');
    expect($categories)->toHaveKey('quality_control');
    expect($categories)->toHaveKey('warehouse');
    expect($categories)->toHaveKey('office');
    expect($categories['production'])->toBe('Lini Produksi');
});
it('appends image_url attribute when converted to array or json', function () {
    $gallery = new LandingGallery([
        'title' => 'Sample Gallery',
        'category' => 'facility',
        'image_path' => 'https://example.com/sample.jpg',
        'is_active' => true,
    ]);

    $array = $gallery->toArray();
    expect($array)->toHaveKey('image_url');
    expect($array['image_url'])->toBe('https://example.com/sample.jpg');
});
