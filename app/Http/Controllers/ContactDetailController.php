<?php

namespace App\Http\Controllers;

use App\Models\ContactDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactDetailController extends Controller
{
    private const DAYS = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    public function edit(): View
    {
        return view('contact-details.edit', ['contact' => ContactDetail::first() ?? new ContactDetail, 'days' => self::DAYS]);
    }

    public function update(Request $request): JsonResponse
    {
        $phone = ['required', 'regex:/^\+\d{8,15}$/'];

        $data = $request->validate([
            'hotline_display' => ['required', 'string', 'max:40'],
            'hotline_e164' => $phone,
            'whatsapp' => $phone,
            'email' => ['required', 'email', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:100', 'required_if:show_floor,1'],
            'show_floor' => ['boolean'],
            'locality' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:10'],
            'opening_days' => ['nullable', 'array', 'required_if:show_hours,1'],
            'opening_days.*' => [Rule::in(self::DAYS)],
            'opens' => ['nullable', 'date_format:H:i', 'required_if:show_hours,1'],
            'closes' => ['nullable', 'date_format:H:i', 'required_if:show_hours,1', 'after:opens'],
            'show_hours' => ['boolean'],
        ], [
            'hotline_e164.regex' => 'Use + and 8 to 15 digits, with no spaces, such as +8801713903025.',
            'whatsapp.regex' => 'Use + and 8 to 15 digits, with no spaces, such as +8801713903025.',
            'floor.required_if' => 'Enter the floor, or switch off "Show on website".',
            'opening_days.required_if' => 'Choose the opening days, or switch off "Show on website".',
            'opens.required_if' => 'Enter the opening time, or switch off "Show on website".',
            'closes.required_if' => 'Enter the closing time, or switch off "Show on website".',
            'closes.after' => 'The closing time must be after the opening time.',
        ]);

        // Keep the week in order, however the boxes were ticked.
        $data['opening_days'] = array_values(array_intersect(self::DAYS, $data['opening_days'] ?? [])) ?: null;

        $contact = ContactDetail::first() ?? new ContactDetail;
        $contact->fill($data)->save();

        session()->flash('status', 'Contact details saved.');

        return response()->json(['message' => 'Contact details saved.']);
    }
}
