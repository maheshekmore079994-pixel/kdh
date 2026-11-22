<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnquiryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        // Honeypot: real visitors never fill this hidden field.
        if ($request->filled('website_url')) {
            return redirect()->route('site.contact')->with('enquiry_sent', true);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{10,15}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'service_interest' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        Enquiry::query()->create($validated + ['ip' => $request->ip()]);

        return redirect()->route('site.contact')->with('enquiry_sent', true);
    }
}
