<?php

namespace App\Exports\Admin;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\MembershipTier;

class UserImportTemplateExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles, WithTitle, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Sample India-based data row
        return collect([
            [
                'full_name' => 'Aryan Sharma',
                'email' => 'aryan@example.com',
                'phone' => '+919876543210',
                'membership_tier_code' => 'PAVILION',
                'membership_expiry_date' => '2030-12-31',
                'dob' => '1985-06-15',
                'country' => 'India',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'preferred_formats' => 'test,odi',
                'eras' => 'modern',
                'has_acquired_memorabilia_before' => 'no',
                'focus' => 'legacy',
                'investment_horizon' => '5',
                'interests' => 'bats,balls',
                'postal_code' => '400001'
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'full_name', 'email', 'phone', 'membership_tier_code', 'membership_expiry_date',
            'dob', 'country', 'city', 'state',
            'preferred_formats', 'eras', 'has_acquired_memorabilia_before', 
            'focus', 'investment_horizon', 'interests', 'postal_code'
        ];
    }

    public function map($row): array
    {
        return [
            $row['full_name'],
            $row['email'],
            $row['phone'],
            $row['membership_tier_code'],
            $row['membership_expiry_date'],
            $row['dob'],
            $row['country'],
            $row['city'],
            $row['state'],
            $row['preferred_formats'],
            $row['eras'],
            $row['has_acquired_memorabilia_before'],
            $row['focus'],
            $row['investment_horizon'],
            $row['interests'],
            $row['postal_code'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30, // full_name
            'B' => 35, // email
            'C' => 20, // phone
            'D' => 25, // membership_tier_code
            'E' => 25, // membership_expiry_date
            'F' => 15, // dob
            'G' => 20, // country
            'H' => 20, // city
            'I' => 20, // state
            'J' => 30, // preferred_formats
            'K' => 25, // eras
            'L' => 35, // has_acquired_memorabilia_before
            'M' => 20, // focus
            'N' => 20, // investment_horizon
            'O' => 30, // interests
            'P' => 20, // postal_code
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
        
        return [
            // Style the first row as header
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '405189'] // Velzon primary color approximate
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Users Import Template';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // 1. Data Validation for Column D (membership_tier_code)
                // Use Rows 2 to 100 for now as a reasonable range
                $tiers = MembershipTier::where('is_active', true)->pluck('code')->toArray();
                if (!empty($tiers)) {
                    $validation = $sheet->getCell('D2')->getDataValidation();
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(false);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Input error');
                    $validation->setError('Value is not in list.');
                    $validation->setPromptTitle('Pick from list');
                    $validation->setPrompt('Please pick a value from the dropdown.');
                    $validation->setFormula1('"' . implode(',', $tiers) . '"');
                    
                    // Apply validation to the entire range D2:D100
                    $sheet->setDataValidation("D2:D100", $validation);
                }

                // 2. Date formatting for Column E (membership_expiry_date)
                $sheet->getStyle('E2:E100')
                    ->getNumberFormat()
                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD2);

                // 3. Date formatting for Column F (dob)
                $sheet->getStyle('F2:F100')
                    ->getNumberFormat()
                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD2);
            },
        ];
    }
}
