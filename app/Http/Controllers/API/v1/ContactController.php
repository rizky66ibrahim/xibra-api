<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\ContactResource;
use App\Helpers\ApiResponse;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'string', 'email', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:20'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $contact = Contact::create($validator->validated());

        return ApiResponse::created(new ContactResource($contact), 'Contact message sent');
    }

    public function index(Request $request)
    {
        // Protected: only authenticated users can list contacts
        $contacts = Contact::latest()->paginate(15);
        return ApiResponse::success($contacts, 'Contacts retrieved');
    }

    public function show(Request $request, Contact $contact)
    {
        return ApiResponse::success(new ContactResource($contact), 'Contact retrieved');
    }

    public function update(Request $request, Contact $contact)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['sometimes', 'string', 'in:new,read,replied'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $contact->update($validator->validated());
        return ApiResponse::success(new ContactResource($contact), 'Contact updated');
    }

    public function destroy(Request $request, Contact $contact)
    {
        $contact->delete();
        return ApiResponse::success(null, 'Contact deleted');
    }
}
