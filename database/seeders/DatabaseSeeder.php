<?php

namespace Database\Seeders;

use App\Models\ChecklistTemplate;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentVersionService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 0. Seed Document Types
        $this->call(DocumentTypeSeeder::class);

        // 1. Create Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@macm.lk'],
            [
                'name' => 'MACM',
                'password' => bcrypt('password'),
                'role' => User::ROLE_ADMIN,
            ]
        );

        $editor1 = User::firstOrCreate(
            ['email' => 'taniya@macm.lk'],
            [
                'name' => 'Sehani Taniya',
                'password' => bcrypt('password'),
                'role' => User::ROLE_EDITOR,
            ]
        );

        $editor2 = User::firstOrCreate(
            ['email' => 'alex@example.com'],
            [
                'name' => 'Alex Miller (Doc Creator)',
                'password' => bcrypt('password'),
                'role' => User::ROLE_EDITOR,
            ]
        );

        $viewer = User::firstOrCreate(
            ['email' => 'viewer@example.com'],
            [
                'name' => 'Audit Viewer',
                'password' => bcrypt('password'),
                'role' => User::ROLE_VIEWER,
            ]
        );

        // 2. Checklist Templates per Document Type
        $checklists = [
            // Proforma Invoice (E / EL)
            [
                'document_type' => Document::TYPE_PROFORMA,
                'items' => [
                    ['item_text' => 'Verify Customer Company Name and Country', 'hint' => 'Ensure exact legal entity name matches registry', 'is_required' => true],
                    ['item_text' => 'Confirm Currency Selection (AED or USD)', 'hint' => 'Verify against agreed commercial proposal', 'is_required' => true],
                    ['item_text' => 'Check Item Codes, Quantities & Unit Prices', 'hint' => 'Cross check SKU against catalogue prices', 'is_required' => true],
                    ['item_text' => 'Calculate Net & Gross Weights', 'hint' => 'Needed for freight rate estimation', 'is_required' => false],
                    ['item_text' => 'Verify Shipment Carrier Quote (DHL, Air, Sea)', 'hint' => 'Compare system amount with given carrier quote', 'is_required' => false],
                    ['item_text' => 'Confirm Payment Terms & Bank Details', 'hint' => 'Include IBAN/SWIFT instructions on document', 'is_required' => true],
                ],
            ],
            // Invoice (N)
            [
                'document_type' => Document::TYPE_INVOICE,
                'items' => [
                    ['item_text' => 'Check Reference Proforma / Purchase Order number', 'hint' => 'Ensure original quote reference is recorded', 'is_required' => true],
                    ['item_text' => 'Confirm Recipient Billing Address and VAT/TRN', 'hint' => 'Must match destination tax requirements', 'is_required' => true],
                    ['item_text' => 'Verify Final Delivered Item Quantities', 'hint' => 'Must correspond exactly with Packing List', 'is_required' => true],
                    ['item_text' => 'Confirm Total Gross and Net Weights in kg', 'hint' => 'Ensure weights reconcile with carrier manifest', 'is_required' => true],
                    ['item_text' => 'Audit Shipment Costs (Checked Weight vs Given Amount)', 'hint' => 'Verify freight markup is applied', 'is_required' => false],
                    ['item_text' => 'Verify Final Total and Currency against payment', 'hint' => 'Double-check AED / USD totals', 'is_required' => true],
                ],
            ],
            // Packing List (W)
            [
                'document_type' => Document::TYPE_PACKING_LIST,
                'items' => [
                    ['item_text' => 'Verify Item Codes against physical packages', 'hint' => 'Ensure no missing carton labels', 'is_required' => true],
                    ['item_text' => 'Calculate and enter Total Net Weight (kg)', 'hint' => 'Sum of products without cartons', 'is_required' => true],
                    ['item_text' => 'Calculate and enter Total Gross Weight (kg)', 'hint' => 'Products with packaging and pallets', 'is_required' => true],
                    ['item_text' => 'Specify Box/Carton breakdown in item description', 'hint' => 'E.g., Box 1/5, Box 2/5', 'is_required' => false],
                    ['item_text' => 'Confirm Transport method (DHL / Air / Sea)', 'hint' => 'Ensure airway bill / booking matches', 'is_required' => true],
                ],
            ],
            // Reserve (R suffix)
            [
                'document_type' => Document::TYPE_RESERVE,
                'items' => [
                    ['item_text' => 'Verify Base Proforma or Invoice number (ends with R)', 'hint' => 'Ex: E26211R', 'is_required' => true],
                    ['item_text' => 'Confirm stock reservation hold in warehouse', 'hint' => 'Items must be tagged as reserved', 'is_required' => true],
                    ['item_text' => 'Set reservation expiry date in notes', 'hint' => 'Standard holds are valid for 7 days', 'is_required' => true],
                    ['item_text' => 'Confirm client commitment deposit received', 'hint' => 'Verify bank receipt or token payment', 'is_required' => false],
                ],
            ],
            // Credit Note (CR suffix)
            [
                'document_type' => Document::TYPE_CREDIT_NOTE,
                'items' => [
                    ['item_text' => 'Confirm original Invoice number credited', 'hint' => 'Must link to original invoice', 'is_required' => true],
                    ['item_text' => 'State reason for credit note in notes field', 'hint' => 'Goods return, pricing adjustment, or shortage', 'is_required' => true],
                    ['item_text' => 'Confirm returned goods inspection received', 'hint' => 'Required if physical inventory was returned', 'is_required' => false],
                    ['item_text' => 'Verify credit amount does not exceed invoice total', 'hint' => 'Audit check', 'is_required' => true],
                ],
            ],
            // Delivery Note (D suffix)
            [
                'document_type' => Document::TYPE_DELIVERY_NOTE,
                'items' => [
                    ['item_text' => 'Confirm Delivery Address & On-site Contact Person', 'hint' => 'Include phone number and gate pass notes', 'is_required' => true],
                    ['item_text' => 'Verify Driver / Courier details', 'hint' => 'Vehicle plate or courier tracking number', 'is_required' => true],
                    ['item_text' => 'Verify physical goods tally with item list', 'hint' => 'Ensure warehouse inspection signed', 'is_required' => true],
                    ['item_text' => 'Ensure Customer Receipt Signature space is clear', 'hint' => 'Driver will get physical stamp/signature', 'is_required' => true],
                ],
            ],
            // Clearing Invoice (C suffix)
            [
                'document_type' => Document::TYPE_CLEARING_INVOICE,
                'items' => [
                    ['item_text' => 'Verify Customs Declaration Number', 'hint' => 'Reference port clearance document number', 'is_required' => true],
                    ['item_text' => 'Check Duty, VAT & Port Handling Fee breakdown', 'hint' => 'Attach official customs receipt', 'is_required' => true],
                    ['item_text' => 'Match weights against Bill of Lading (B/L) / AWB', 'hint' => 'Net and Gross weights must match', 'is_required' => true],
                    ['item_text' => 'Verify currency conversion rate if paid in local AED', 'hint' => 'Document USD equivalent', 'is_required' => false],
                ],
            ],
            // Cash Receipt (Custom / RCP)
            [
                'document_type' => Document::TYPE_CASH_RECEIPT,
                'items' => [
                    ['item_text' => 'Confirm Received Amount in Currency (AED / USD)', 'hint' => 'Match exact cash / bank deposit amount', 'is_required' => true],
                    ['item_text' => 'Verify paying customer account details', 'hint' => 'Check party ledger', 'is_required' => true],
                    ['item_text' => 'Record Payment Mode (Cash, Cheque, Online Transfer)', 'hint' => 'Add reference/transaction code in notes', 'is_required' => true],
                    ['item_text' => 'Issue official receipt acknowledgment to customer', 'hint' => 'Stamp or email copy', 'is_required' => true],
                ],
            ],
        ];

        foreach ($checklists as $section) {
            foreach ($section['items'] as $index => $item) {
                ChecklistTemplate::updateOrCreate(
                    [
                        'document_type' => $section['document_type'],
                        'item_text' => $item['item_text'],
                    ],
                    [
                        'hint' => $item['hint'],
                        'is_required' => $item['is_required'],
                        'sort_order' => $index + 1,
                        'is_active' => true,
                    ]
                );
            }
        }

        // 3. Seed Sample Document: E26211 (Proforma Invoice)
        $doc = Document::updateOrCreate(
            ['document_number' => 'E26211'],
            [
                'document_type' => Document::TYPE_PROFORMA,
                'company_name' => 'Gulf Apex Trading LLC',
                'country' => 'United Arab Emirates',
                'address' => 'Warehouse 14, Al Quoz Industrial Area 3, Dubai, UAE',
                'contact_details' => 'Attn: Mr. Tariq Mansour | Phone: +971 4 338 1234 | Email: tariq@gulfapex.ae',
                'document_date' => now()->format('Y-m-d'),
                'currency' => 'USD',
                'total_net_weight' => 142.500,
                'total_gross_weight' => 165.200,
                'subtotal' => 14650.00,
                'final_total' => 15450.00,
                'current_version' => 1,
                'status' => 'active',
                'notes' => 'Payment terms: 30% advance, balance upon Bill of Lading copy. Delivery within 10 days.',
                'created_by' => $editor1->id,
                'updated_by' => $editor1->id,
            ]
        );

        $doc->items()->delete();
        $items = [
            ['item_code' => 'PMP-8820', 'description' => 'Industrial Hydraulic Pump Set 2.5KW', 'unit_amount' => 4, 'unit_price' => 1850.00, 'total_amount' => 7400.00, 'sort_order' => 1],
            ['item_code' => 'VAL-3100', 'description' => 'High Pressure Flow Control Valve 1.5"', 'unit_amount' => 10, 'unit_price' => 425.00, 'total_amount' => 4250.00, 'sort_order' => 2],
            ['item_code' => 'FIT-9921', 'description' => 'Stainless Steel Coupling & O-Ring Kits', 'unit_amount' => 20, 'unit_price' => 150.00, 'total_amount' => 3000.00, 'sort_order' => 3],
        ];
        foreach ($items as $item) {
            $doc->items()->create($item);
        }

        $doc->shipmentCosts()->delete();
        $costs = [
            ['method' => 'dhl', 'checked_weight' => 165.20, 'system_amount' => 920.00, 'added_amount' => 130.00, 'given_amount' => 800.00],
            ['method' => 'air_freight', 'checked_weight' => 165.20, 'system_amount' => 1100.00, 'added_amount' => 150.00, 'given_amount' => 950.00],
            ['method' => 'sea_freight', 'checked_weight' => 165.20, 'system_amount' => 450.00, 'added_amount' => 100.00, 'given_amount' => 400.00],
        ];
        foreach ($costs as $cost) {
            $doc->shipmentCosts()->create($cost);
        }

        // Create Version 1 snapshot
        $versionService = new DocumentVersionService;
        $versionService->createSnapshot($doc, $editor1, 'Initial Proforma creation');
    }
}
