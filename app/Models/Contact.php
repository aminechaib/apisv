<?php
// app/models/Contact.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage; // <-- Make sure this is imported

class Contact extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'activity',
        'address',
        'website',
        'image_path',
        'confidence_score',
        'needs_review',
        'status',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['image_url']; // <-- ADD THIS LINE

    /**
     * Get the full URL for the contact's image.
     *
     * This is the new accessor method.
     *
     * @return string|null
     */
    public function getImageUrlAttribute( ): ?string // <-- ADD THIS ENTIRE METHOD
    {
        if ($this->image_path) {
            // Use the asset() helper which correctly uses APP_URL
            // to build the full URL.
            return asset(Storage::url($this->image_path));
        }

        return null;
    }
}
