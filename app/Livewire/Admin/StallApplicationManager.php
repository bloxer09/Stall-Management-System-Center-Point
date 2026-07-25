<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\StallApplication;
use App\Services\MailjetMailer;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class StallApplicationManager extends Component
{
    public $filterStatus = 'all';

    public function updateStatus($applicationId, $status, MailjetMailer $mailjet)
    {
        $application = StallApplication::findOrFail($applicationId);
        $application->update([
            'status' => $status,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        AuditLog::log(
            Auth::id(),
            "Updated application #{$application->id} status to {$status}",
            'StallApplication',
            $application->id
        );

        // Send Email Notification to Applicant
        try {
            $mailjet->sendEmail(
                $application->applicant_email,
                $application->applicant_name,
                "Stall Application Status Update - {$status}",
                "<p>Hello {$application->applicant_name},</p><p>Your stall reservation application for <strong>{$application->business_name}</strong> has been updated to: <strong>" . strtoupper($status) . "</strong>.</p>"
            );
        } catch (\Exception $e) {
            // Ignore email error in offline environment
        }

        session()->flash('message', "Application #{$application->id} status updated to {$status}.");
    }

    public function render()
    {
        $query = StallApplication::with(['preferredStall', 'reviewer'])->latest();

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        $applications = $query->paginate(10);

        return view('livewire.admin.stall-application-manager', [
            'applications' => $applications,
        ]);
    }
}
