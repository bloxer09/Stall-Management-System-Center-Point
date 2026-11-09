<?php

namespace App\Livewire\Concerns;

use App\Rules\TicketMedia;
use Illuminate\Validation\ValidationException;

trait AcceptsTicketMedia
{
    public function updatedIncomingAttachments(): void
    {
        $this->resetValidation();
        try {
            $this->validate(['incomingAttachments.*' => TicketMedia::rules()]);
        } catch (ValidationException $exception) {
            $this->incomingAttachments = [];
            throw $exception;
        }
        foreach ($this->incomingAttachments as $file) {
            $this->attachments[] = $file;
        }
        $this->incomingAttachments = [];
    }
}
