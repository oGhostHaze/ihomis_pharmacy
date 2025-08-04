<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription Receipt</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            padding: 10px;
        }

        .receipt-container {
            /* Default for 76mm POS paper (approximately 288px at 96dpi) */
            width: 76mm;
            max-width: 100%;
            margin: 0 auto;
            background: white;
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 11px;
            line-height: 1.3;
        }

        .header {
            text-align: center;
            border-bottom: 1px dashed #333;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }

        .pharmacy-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 2px;
        }

        .pharmacy-info {
            font-size: 9px;
            line-height: 1.2;
        }

        .rx-number {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            margin: 8px 0;
            padding: 4px;
            background: #f8f8f8;
            border: 1px solid #ddd;
        }

        .patient-info,
        .prescriber-info,
        .medication-info {
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px dotted #ccc;
        }

        .section-title {
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 3px;
            text-decoration: underline;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-size: 10px;
        }

        .info-label {
            font-weight: bold;
            flex-shrink: 0;
            margin-right: 8px;
        }

        .info-value {
            text-align: right;
            word-break: break-word;
        }

        .medication-name {
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            margin: 6px 0;
            padding: 4px;
            border: 2px solid #333;
        }

        .directions {
            background: #f9f9f9;
            padding: 6px;
            margin: 6px 0;
            border-left: 3px solid #333;
            font-size: 10px;
        }

        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 6px;
            margin: 6px 0;
            font-size: 9px;
            text-align: center;
        }

        .footer {
            text-align: center;
            font-size: 8px;
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #333;
        }

        .barcode-placeholder {
            text-align: center;
            font-family: 'Courier New', monospace;
            font-size: 8px;
            letter-spacing: 1px;
            margin: 8px 0;
            padding: 4px;
            background: #f0f0f0;
        }

        /* Responsive breakpoints */
        @media (min-width: 80mm) {
            .receipt-container {
                width: 80mm;
                font-size: 12px;
            }

            .pharmacy-name {
                font-size: 15px;
            }

            .medication-name {
                font-size: 13px;
            }
        }

        @media (min-width: 100mm) {
            .receipt-container {
                width: 100mm;
                padding: 12px;
                font-size: 13px;
            }

            .pharmacy-name {
                font-size: 16px;
            }

            .medication-name {
                font-size: 14px;
            }

            .info-row {
                font-size: 11px;
            }
        }

        @media (min-width: 150mm) {
            .receipt-container {
                width: 150mm;
                padding: 15px;
                font-size: 14px;
            }

            .pharmacy-name {
                font-size: 18px;
            }

            .rx-number {
                font-size: 14px;
            }

            .medication-name {
                font-size: 16px;
            }
        }

        /* Mobile responsive */
        @media (max-width: 320px) {
            .receipt-container {
                width: 100%;
                padding: 6px;
                font-size: 10px;
            }

            .pharmacy-name {
                font-size: 12px;
            }

            .info-row {
                flex-direction: column;
                text-align: left;
            }

            .info-value {
                text-align: left;
                margin-left: 10px;
            }
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .receipt-container {
                border: none;
                box-shadow: none;
                width: 76mm;
                margin: 0;
            }
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <!-- Header Section -->
        <div class="header">
            <div class="pharmacy-name">MARIANO MARCOS MEM HOSP. MED CTR</div>
            <div class="pharmacy-info">
                Brgy. 6 San Julian<br>
                City of Batac, Ilocos Norte 2906
            </div>
        </div>

        <!-- Prescription Number -->
        <div class="rx-number">
            RX# {{ $encounter->enccode ?? '1234567' }}
        </div>

        <!-- Patient Information -->
        <div class="patient-info">
            <div class="section-title">Patient Information</div>
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span
                    class="info-value">{{ $encounter->patfirst . ' ' . $encounter->patmiddle . ' ' . $encounter->patlast . ' ' . $encounter->patsuffix }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">DOB:</span>
                <span
                    class="info-value">{{ $encounter->patbdate ? $encounter->patbdate->format('m/d/Y') : 'Not Indicated' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Address:</span>
                <span class="info-value">{{ $encounter->address ?? '456 Patient St.' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span class="info-value">{{ $patient->phone ?? '(077) 123-4567' }}</span>
            </div>
        </div>

        <!-- Prescriber Information -->
        <div class="prescriber-info">
            <div class="section-title">Prescriber</div>
            <div class="info-row">
                <span class="info-label">Dr:</span>
                <span
                    class="info-value">{{ $prescriber->firstname . ' ' . $prescriber->middlename . ' ' . $prescriber->lastname . ' ' . $prescriber->empdegree . $prescriber->empalias }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">License:</span>
                <span class="info-value">{{ $prescriber->licno }}</span>
            </div>
        </div>
        @forelse($prescription->data->all() as $medication)
            <!-- Medication Information -->
            <div class="medication-info">
                <div class="section-title">Medication</div>
                <div class="medication-name">
                    {{ $medication->dm->drug_concat() }}
                </div>
                <div class="info-row">
                    <span class="info-label">Generic:</span>
                    <span class="info-value">{{ $medication->dm->generic->gendesc }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Strength:</span>
                    <span class="info-value">{{ $medication->dm->dmdnost . ' ' . $medication->dm->strecode }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Form:</span>
                    <span class="info-value">{{ $medication->dm->formcode }}</span>
                </div>
            </div>

            <!-- Directions -->
            <div class="directions">
                <strong>DIRECTIONS FOR USE:</strong><br>
                {{ $prescription->directions ?? 'Take one capsule by mouth three times daily with food for 10 days. Complete entire course even if feeling better.' }}
            </div>

            <!-- Dispensing Information -->
            <div class="info-row">
                <span class="info-label">Qty Dispensed:</span>
                <span class="info-value">{{ $prescription->quantity_dispensed ?? '30' }}
                    {{ $medication->form ?? 'capsules' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Days Supply:</span>
                <span class="info-value">{{ $prescription->days_supply ?? '10' }} days</span>
            </div>
            <div class="info-row">
                <span class="info-label">Refills Left:</span>
                <span class="info-value">{{ $prescription->refills_remaining ?? '2' }} of
                    {{ $prescription->refills_authorized ?? '3' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Date Filled:</span>
                <span
                    class="info-value">{{ $prescription->filled_date ? $prescription->filled_date->format('m/d/Y H:i') : now()->format('m/d/Y H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Pharmacist:</span>
                <span class="info-value">{{ $pharmacist->name ?? 'RPh. Ana Cruz' }}</span>
            </div>

            <!-- Warning Box -->
            @if ($medication->warnings ?? true)
                <div class="warning-box">
                    <strong>⚠ IMPORTANT:</strong>
                    {{ $medication->warning_text ?? 'Take with food. May cause drowsiness. Complete full course.' }}
                </div>
            @endif
        @empty
        @endforelse
        <!-- Barcode -->
        <div class="barcode-placeholder">
            ||||| |||| | |||| ||||| || ||||<br>
            {{ $prescription->rx_number ?? '1234567' }}
        </div>

        <!-- Footer -->
        <div class="footer">
            Thank you for choosing us!<br>
            <small>{{ now()->format('Y-m-d H:i:s') }}</small>
        </div>
    </div>
</body>

</html>
