<?php
// app/Http/Controllers/Api/V1/ContactController.php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contact;
use App\Jobs\ProcessBusinessCard;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage; // <-- Add this
use App\Services\OcrService; // <-- A hypothetical OCR service
use App\Exports\ContactsExport; // <-- Add this
use Maatwebsite\Excel\Facades\Excel; // <-- And this
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

   public function processCard(Request $request)
{
    // 1. Validate the uploaded file
    $validator = Validator::make($request->all(), [
        'card_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB Max
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    // 2. Store the image
    $path = $request->file('card_image')->store('public/cards');

    // 3. Create a preliminary contact record
    $contact = Contact::create([
        'status' => 'processing',
        'image_path' => $path,
    ]);

    // 4. Extract text from the image using our service
    $extractedText = OcrService::extractText(storage_path('app/' . $path));

    // 5. Handle OCR Failure
    // If the service returns null, the OCR failed. We should stop.
    if (is_null($extractedText)) {
        $contact->update(['status' => 'failed', 'needs_review' => true]);
        return response()->json([
            'message' => 'Failed to extract text from the image. The contact has been flagged for review.',
            'contact_id' => $contact->id,
        ], 500); // 500 Internal Server Error is appropriate here
    }

    // 6. Dispatch the job ONCE with the successfully extracted text
    ProcessBusinessCard::dispatch($contact, $extractedText);

    // 7. Return a successful "Accepted" response
    return response()->json([
        'message' => 'Card image received. Processing has started.',
        'contact_id' => $contact->id,
        'image_url' => Storage::url($path),
        'status' => $contact->status,
    ], 202);
}

     // In ContactController.php -> listContacts()

public function listContacts()
{
    $contacts = Contact::where('status', 'validated')
        ->orderBy('created_at', 'desc')
        ->paginate(15);

    return response()->json($contacts);
}

       public function processExtractedText(Request $request)
    {
        // 1. Validate that we received the text from the Flutter app
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|min:10',
            'image_url' => 'nullable|string|url' // Optional: URL of the card image
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 2. Create a preliminary contact record
        $contact = Contact::create([
            'status' => 'processing',
            'image_path' => $request->input('image_url'), // Save the optional image URL
        ]);

        // 3. Dispatch the job, passing the extracted text from the request
        ProcessBusinessCard::dispatch($contact, $request->input('text'));

        // 4. Return a response to the client
        return response()->json([
            'message' => 'Text received. Processing has started.',
            'contact_id' => $contact->id,
            'status' => $contact->status,
        ], 202);
    }
    /**
     * Store a newly created resource in storage.
     */
    

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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
