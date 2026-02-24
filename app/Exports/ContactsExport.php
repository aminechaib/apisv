<?php
// app/Exports/ContactsExport.php

namespace App\Exports;

use App\Models\Contact;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ContactsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // We only want to export validated contacts
        return Contact::where('status', 'validated')->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        // These are the column headers in the Excel file
        return [
            'Name',
            'Email',
            'Phone',
            'Company',
            'Job Title',
            'Address',
            'Website',
            'Date Processed',
        ];
    }

    /**
     * @var Contact $contact
     * @return array
     */
    public function map($contact): array
    {
        // This maps the data from each contact to the columns
        return [
            $contact->name,
            $contact->email,
            $contact->phone,
            $contact->company,
            $contact->activity,
            $contact->address,
            $contact->website,
            $contact->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
