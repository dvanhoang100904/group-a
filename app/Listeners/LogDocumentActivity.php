<?php

namespace App\Listeners;

use App\Events\DocumentViewed;
use Illuminate\Support\Facades\Request;

class LogDocumentActivity
{
    public function handle(DocumentViewed $event)
    {
        $document = $event->document;
        $user = auth()->user(); // ở đây auth() vẫn có vì chưa logout

        \App\Models\AccessLog::create([
            'user_id'     => $user?->user_id,
            'action'      => 'document_view',
            'target_id'   => $document->document_id,
            'target_type' => 'document',
            'ip_address'  => Request::ip() ?? '127.0.0.1',
            'user_agent'  => Request::userAgent() ?? 'Unknown',
            'url'         => Request::fullUrl(),
            'description' => "Xem tài liệu #{$document->document_id}: {$document->title}",
        ]);
    }
}