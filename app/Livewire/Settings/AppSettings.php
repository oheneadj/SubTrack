<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class AppSettings extends Component
{
    use WithFileUploads;

    // App Identity
    public $appName;
    public $logo;
    public $currentLogo;

    // Business Info
    public $companyName;
    public $contactEmail;
    public $businessPhone;
    public $businessWebsite;

    // Payment Details
    public $bankName;
    public $bankAccountName;
    public $bankAccountNumber;
    public $paypalEmail;

    // Invoicing Defaults
    public $invoicePrefix;
    public $invoiceDueDays;
    public $invoiceFooter;
    public $senderName;
    public $senderTitle;

    // Notifications
    public $reminderDays;

    protected $rules = [
        'appName'           => 'required|string|max:50',
        'companyName'       => 'required|string|max:100',
        'contactEmail'      => 'required|email|max:100',
        'businessPhone'     => 'nullable|string|max:30',
        'businessWebsite'   => 'nullable|url|max:255',
        'bankName'          => 'nullable|string|max:100',
        'bankAccountName'   => 'nullable|string|max:100',
        'bankAccountNumber' => 'nullable|string|max:50',
        'paypalEmail'       => 'nullable|email|max:100',
        'invoicePrefix'     => 'required|string|max:10',
        'invoiceDueDays'    => 'required|integer|min:1|max:90',
        'invoiceFooter'     => 'nullable|string|max:500',
        'senderName'        => 'nullable|string|max:100',
        'senderTitle'       => 'nullable|string|max:100',
        'reminderDays'      => 'nullable|string|max:50',
        'logo'              => 'nullable|image|max:1024',
    ];

    public function mount()
    {
        $s = Setting::getAllAsArray();

        $this->appName           = $s['app_name'] ?? config('app.name');
        $this->currentLogo       = $s['logo_path'] ?? null;
        $this->companyName       = $s['company_name'] ?? ($s['business_name'] ?? '');
        $this->contactEmail      = $s['contact_email'] ?? ($s['business_email'] ?? '');
        $this->businessPhone     = $s['business_phone'] ?? '';
        $this->businessWebsite   = $s['business_website'] ?? '';
        $this->bankName          = $s['bank_name'] ?? '';
        $this->bankAccountName   = $s['bank_account_name'] ?? '';
        $this->bankAccountNumber = $s['bank_account_number'] ?? '';
        $this->paypalEmail       = $s['paypal_email'] ?? '';
        $this->invoicePrefix     = $s['invoice_prefix'] ?? 'INV';
        $this->invoiceDueDays    = $s['invoice_due_days'] ?? 14;
        $this->invoiceFooter     = $s['invoice_footer'] ?? '';
        $this->senderName        = $s['sender_name'] ?? '';
        $this->senderTitle       = $s['sender_title'] ?? '';
        $this->reminderDays      = $s['reminder_days'] ?? '30,14,7';
    }

    public function save()
    {
        $this->validate();

        if ($this->logo) {
            if ($this->currentLogo) {
                Storage::delete('public/' . $this->currentLogo);
            }
            $path = $this->logo->store('logos', 'public');
            Setting::set('logo_path', $path);
            $this->currentLogo = $path;
            $this->logo = null;
        }

        Setting::set('app_name', $this->appName);
        Setting::set('company_name', $this->companyName);
        Setting::set('business_name', $this->companyName);
        Setting::set('contact_email', $this->contactEmail);
        Setting::set('business_email', $this->contactEmail);
        Setting::set('business_phone', $this->businessPhone);
        Setting::set('business_website', $this->businessWebsite);
        Setting::set('bank_name', $this->bankName);
        Setting::set('bank_account_name', $this->bankAccountName);
        Setting::set('bank_account_number', $this->bankAccountNumber);
        Setting::set('paypal_email', $this->paypalEmail);
        Setting::set('invoice_prefix', $this->invoicePrefix);
        Setting::set('invoice_due_days', $this->invoiceDueDays);
        Setting::set('invoice_footer', $this->invoiceFooter);
        Setting::set('sender_name', $this->senderName);
        Setting::set('sender_title', $this->senderTitle);
        Setting::set('reminder_days', $this->reminderDays);

        session()->flash('success', 'Settings updated successfully.');
    }

    public function render()
    {
        return view('livewire.settings.app-settings')
            ->layout('layouts.app');
    }
}
