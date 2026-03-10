<?php

use App\Jobs\ProcessBusinessCard;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('a business card image can be uploaded and queued for processing', function () {
    Queue::fake();
    Storage::fake('public');

    $response = $this->postJson('/api/process-card', [
        'card_image' => UploadedFile::fake()->image('card.jpg', 1400, 900)->size(2048),
    ]);

    $response
        ->assertAccepted()
        ->assertJsonPath('status', 'processing')
        ->assertJsonStructure([
            'message',
            'contact_id',
            'image_url',
            'status',
        ]);

    $contact = Contact::query()->first();

    expect($contact)->not->toBeNull();
    expect($contact->status)->toBe('processing');
    expect($contact->image_path)->toStartWith('cards/');

    Storage::disk('public')->assertExists($contact->image_path);

    Queue::assertPushed(ProcessBusinessCard::class);

    $this->get('/api/cards/'.$contact->id.'/image')->assertOk();
});
