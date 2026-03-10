<?php

// app/Http/Controllers/Api/V1/ContactController.php

namespace App\Http\Controllers\Api\V1;

use App\Exports\ContactsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCardImageRequest;
use App\Jobs\ProcessBusinessCard;
use App\Models\Contact;
use App\Services\OcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function exportToExcel()
    {
        return Excel::download(new ContactsExport, 'contacts.xlsx');
    }

    public function processCard(StoreCardImageRequest $request)
    {
        $path = $request->file('card_image')->store('cards', 'public');

        $contact = Contact::create([
            'status' => 'processing',
            'image_path' => $path,
        ]);

        $extractedText = trim($request->input('text', ''));
        if ($extractedText === '') {
            $extractedText = trim((string) (OcrService::extractText(Storage::disk('public')->path($path)) ?? ''));
        }

        if ($extractedText === '') {
            $contact->update(['needs_review' => true]);
        }

        ProcessBusinessCard::dispatch($contact, $extractedText);

        return response()->json([
            'message' => 'Card image received. Processing has started.',
            'contact_id' => $contact->id,
            'image_url' => $contact->image_url,
            'status' => $contact->status,
        ], 202);
    }

    public function image(Contact $contact)
    {
        if (! $contact->image_path) {
            abort(404);
        }

        if (filter_var($contact->image_path, FILTER_VALIDATE_URL)) {
            return redirect()->away($contact->image_path);
        }

        if (! Storage::disk('public')->exists($contact->image_path)) {
            abort(404);
        }

        return Storage::disk('public')->response(
            $contact->image_path,
            null,
            ['Cache-Control' => 'public, max-age=86400']
        );
    }

    public function listContacts()
    {
        $contacts = Contact::where('status', 'validated')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($contacts);
    }

    public function processExtractedText(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|min:10',
            'image_url' => 'nullable|string|url',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $contact = Contact::create([
            'status' => 'processing',
            'image_path' => $request->input('image_url'),
        ]);

        ProcessBusinessCard::dispatch($contact, $request->input('text'));

        return response()->json([
            'message' => 'Text received. Processing has started.',
            'contact_id' => $contact->id,
            'image_url' => $contact->image_url,
            'status' => $contact->status,
        ], 202);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Contact $contact)
    {
        $validatedData = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:2000',
            'phone' => 'nullable|string|max:2000',
            'company' => 'nullable|string|max:255',
            'activity' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:5000',
            'website' => 'nullable|string|max:255',
        ]);

        $validatedData['needs_review'] = false;

        $contact->update($validatedData);

        return response()->json($contact);
    }

    /**
     * Remove the specified contact from storage.
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return response()->json(null, 204);
    }
}
