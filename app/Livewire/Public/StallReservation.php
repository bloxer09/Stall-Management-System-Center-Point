<?php

namespace App\Livewire\Public;

use App\Models\Notification;
use App\Models\Stall;
use App\Models\StallApplication;
use App\Models\User;
use App\Services\MailjetMailer;
use Livewire\Component;
use Livewire\WithFileUploads;

class StallReservation extends Component
{
    use WithFileUploads;

    public $applicant_name = '';
    public $applicant_email = '';
    public $applicant_phone = '';
    public $business_name = '';
    public $business_type = '';
    public $preferred_stall_id = null;
    public $message = '';
    public $document = null;

    public $submitted = false;

    protected $rules = [
        'applicant_name' => 'required|string|max:100',
        'applicant_email' => 'required|email|max:120',
        'applicant_phone' => 'required|string|max:30',
        'business_name' => 'required|string|max:150',
        'business_type' => 'required|string|max:100',
        'preferred_stall_id' => 'nullable|exists:stalls,id',
        'message' => 'nullable|string|max:1000',
        'document' => 'nullable|file|mimes:pdf,jpg,png|max:5120',
    ];

    public function submit(MailjetMailer $mailjet)
    {
        $this->validate();

        $documentPath = null;
        if ($this->document) {
            $documentPath = $this->document->store('applications', 'public');
        }

        $application = StallApplication::create([
            'applicant_name' => $this->applicant_name,
            'applicant_email' => $this->applicant_email,
            'applicant_phone' => $this->applicant_phone,
            'business_name' => $this->business_name,
            'business_type' => $this->business_type,
            'preferred_stall_id' => $this->preferred_stall_id,
            'message' => $this->message,
            'document' => $documentPath,
            'status' => 'pending',
        ]);

        // Notify Admins
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title' => 'New Stall Application Submitted',
                'message' => "Application from {$this->applicant_name} ({$this->business_name}) for stall selection.",
                'type' => 'info',
                'is_read' => false,
            ]);
        }

        // Email Applicant
        try {
            $mailjet->sendEmail(
                $this->applicant_email,
                $this->applicant_name,
                "Stall Reservation Application Received - Center Point",
                "<p>Hello {$this->applicant_name},</p><p>Thank you for submitting your stall reservation application for <strong>{$this->business_name}</strong>. Our administrative team will review your application and contact you soon.</p>"
            );
        } catch (\Exception $e) {
            // Ignore email error for offline testing
        }

        $this->submitted = true;
    }

    public function render()
    {
        $availableStalls = Stall::with('location')->where('status', 'available')->get();

        return view('livewire.public.stall-reservation', [
            'availableStalls' => $availableStalls,
        ])->layout('layouts.guest');
    }
}
