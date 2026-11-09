<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class StallApplicationForm extends Form
{
    #[Validate('required|string|max:100')]
    public string $applicant_name = '';

    #[Validate('required|email|max:120')]
    public string $applicant_email = '';

    #[Validate]
    public string $applicant_phone = '';

    #[Validate('required|string|max:150')]
    public string $business_name = '';

    #[Validate('required|string|max:100')]
    public string $business_type = '';

    #[Validate('nullable|string|max:1000')]
    public string $message = '';

    #[Validate('nullable|file|mimes:pdf,jpg,jpeg,png|max:5120')]
    public $document = null;

    // Honeypot: real visitors never see or fill this field.
    public string $website = '';

    protected function rules(): array
    {
        return [
            'applicant_phone' => ['required', 'string', 'max:30', new \App\Rules\PhilippineMobileNumber],
        ];
    }

    public function payload(): array
    {
        return [
            'applicant_name' => trim($this->applicant_name),
            'applicant_email' => $this->applicant_email,
            'applicant_phone' => trim($this->applicant_phone),
            'business_name' => trim($this->business_name),
            'business_type' => trim($this->business_type),
            'message' => trim($this->message) ?: null,
        ];
    }
}
