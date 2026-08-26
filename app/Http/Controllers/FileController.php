<?php

namespace App\Http\Controllers;

use App\Models\StallMapping;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileController extends Controller
{
    public function downloadContract(int $mappingId): BinaryFileResponse
    {
        $mapping = StallMapping::findOrFail($mappingId);

        // IDOR check: Only admin or the assigned tenant can download
        if (!auth()->user()->isAdmin() && auth()->id() !== $mapping->user_id) {
            abort(403, 'Unauthorized access to contract document.');
        }

        if (!$mapping->contract_document || !Storage::disk('local')->exists($mapping->contract_document)) {
            abort(404, 'Contract file not found.');
        }

        return response()->download(storage_path('app/' . $mapping->contract_document));
    }

    public function downloadTicketAttachment(int $messageId): BinaryFileResponse
    {
        $message = TicketMessage::with('ticket')->findOrFail($messageId);

        // IDOR check: Only admin or the ticket owner can download
        if (!auth()->user()->isAdmin() && auth()->id() !== $message->ticket->user_id) {
            abort(403, 'Unauthorized access to ticket attachment.');
        }

        if (!$message->attachment_path || !Storage::disk('local')->exists($message->attachment_path)) {
            abort(404, 'Attachment file not found.');
        }

        return response()->download(storage_path('app/' . $message->attachment_path));
    }
}
