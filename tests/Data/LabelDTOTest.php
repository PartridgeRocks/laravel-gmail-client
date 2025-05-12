<?php

namespace PartridgeRocks\GmailClient\Tests\Data;

use Illuminate\Support\Collection;
use PartridgeRocks\GmailClient\Data\Responses\LabelDTO;

it('can create from API response', function () {
    $data = json_decode(file_get_contents(__DIR__ . '/../fixtures/label.json'), true);
    
    $label = LabelDTO::fromApiResponse($data);
    
    expect($label)
        ->toBeInstanceOf(LabelDTO::class)
        ->and($label->id)->toBe('Label_123')
        ->and($label->name)->toBe('Test Label')
        ->and($label->type)->toBe('user')
        ->and($label->messagesTotal)->toBe(0)
        ->and($label->messagesUnread)->toBe(0)
        ->and($label->color)->not()->toBeNull();
});

it('handles missing fields gracefully', function () {
    $data = [
        'id' => 'Label_123',
        'name' => 'Test Label',
    ];
    
    $label = LabelDTO::fromApiResponse($data);
    
    expect($label)
        ->toBeInstanceOf(LabelDTO::class)
        ->and($label->id)->toBe('Label_123')
        ->and($label->name)->toBe('Test Label')
        ->and($label->type)->toBeNull()
        ->and($label->messagesTotal)->toBeNull()
        ->and($label->messagesUnread)->toBeNull()
        ->and($label->color)->toBeNull();
});

it('can create a collection from API response', function () {
    $data = json_decode(file_get_contents(__DIR__ . '/../fixtures/labels-list.json'), true);
    
    $labels = LabelDTO::collectionFromApiResponse($data);
    
    expect($labels)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(2)
        ->and($labels->first())->toBeInstanceOf(LabelDTO::class)
        ->and($labels->first()->id)->toBe('Label_123')
        ->and($labels->first()->name)->toBe('Test Label')
        ->and($labels->last()->id)->toBe('INBOX')
        ->and($labels->last()->name)->toBe('INBOX');
});