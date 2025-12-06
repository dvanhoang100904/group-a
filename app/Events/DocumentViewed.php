<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Document;

class DocumentViewed
{
    use Dispatchable, SerializesModels;

    function __construct(public User $user, public Document $document) {}
}