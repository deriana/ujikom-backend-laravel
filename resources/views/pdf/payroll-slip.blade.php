<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* Tipografi & Dasar */
        body {
            font-family: 'Inter', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 30px;
            background-color: #fff;
        }
        h2 { margin: 0; color: #0f172a; font-size: 20px; font-weight: 800; letter-spacing: -0.5px; }
        h4 {
            margin: 0 0 12px 0;
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }

        /* Layout Tabel */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        td { padding: 6px 0; vertical-align: top; }

        /* Utilitas */
        .text-right { text-align: right; }
        .bold { font-weight: 700; color: #0f172a; }
        .center { text-align: center; }
        .text-muted { color: #64748b; }

        /* Header Box */
        .header-container { margin-bottom: 40px; }
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-success { background-color: #dcfce7; color: #166534; }

        /* Kolom Berdampingan (Earnings & Deductions) */
        .split-section > tbody > tr > td { width: 46%; padding: 0 2%; border: none; }

        /* Styling Baris Tabel Data */
        .data-table tr { border-bottom: 1px solid #f1f5f9; }
        .data-table tr:last-child { border-bottom: none; }
        .total-row { border-top: 2px solid #0f172a !important; }

        /* Net Salary Box */
        .net-salary-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 20px;
            margin-top: 30px;
            border-radius: 8px;
        }
        .net-salary-box td { border: none !important; padding: 0; }

        /* Footer */
        .footer { margin-top: 60px; color: #94a3b8; font-size: 9px; border-top: 1px solid #f1f5f9; padding-top: 20px; }
    </style>
</head>
<body>

<div class="header-container">
    <table style="margin-bottom: 0;">
        <tr>
            <td width="50%">
                @if(!empty($company['logo']))
                    <img src="{{ public_path(parse_url($company['logo'], PHP_URL_PATH)) }}" height="40" style="margin-bottom: 10px;">
                @endif
                <h2>{{ $company['site_name'] ?? 'COMPANY NAME' }}</h2>
                <p style="margin: 4px 0 0 0; color: #64748b; font-weight: 500;">Official Payroll Statement</p>
            </td>
            <td width="50%" class="text-right">
                <div style="font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">PAYSLIP #{{ substr($data['uuid'], 0, 8) }}</div>
                <div style="margin-bottom: 8px;"><span class="badge badge-success">{{ $data['status']['label'] }}</span></div>
                <div class="text-muted">Generated on {{ $data['finalized_at'] }}</div>
            </td>
        </tr>
    </table>
</div>

<table>
    <tr>
        <td width="50%">
            <h4>EMPLOYEE INFORMATION</h4>
            <table class="data-table">
                <tr><td>Nama</td><td class="bold">: {{ $data['employee']['name'] }}</td></tr>
                <tr><td>NIK</td><td>: {{ $data['employee']['nik'] }}</td></tr>
                <tr><td>Jabatan</td><td>: {{ $data['employee']['position']['name'] }}</td></tr>
                <tr><td>Status Kerja</td><td>: {{ $data['employee']['employment_status'] }}</td></tr>
            </table>
        </td>
        <td width="50%">
            <h4>PAY PERIOD</h4>
            <table class="data-table">
                <tr><td>Start Date</td><td>: {{ $data['period']['start'] }}</td></tr>
                <tr><td>End Date</td><td>: {{ $data['period']['end'] }}</td></tr>
                <tr><td>Working Days</td><td>: {{ $data['period']['days'] }} Days</td></tr>
                <tr><td>Currency</td><td>: IDR</td></tr>
            </table>
        </td>
    </tr>
</table>

<table class="split-section">
    <tr>
        <td>
            <h4>EARNINGS</h4>
            <table class="data-table">
                <tr>
                    <td>Basic Salary</td>
                    <td class="text-right">{{ number_format($data['earnings']['base_salary'], 0) }}</td>
                </tr>
                @foreach($data['earnings']['allowances'] ?? [] as $allowance)
                    <tr>
                        <td>{{ $allowance['name'] }}</td>
                        <td class="text-right">{{ number_format($allowance['amount'], 0) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td>Overtime Pay</td>
                    <td class="text-right">{{ number_format($data['earnings']['overtime_pay'], 0) }}</td>
                </tr>
                <tr>
                    <td>Adjustments</td>
                    <td class="text-right">{{ number_format($data['earnings']['manual_adjustment'], 0) }}</td>
                </tr>
                <tr class="total-row bold">
                    <td style="padding-top: 10px;">GROSS SALARY</td>
                    <td class="text-right">{{ number_format($data['earnings']['gross_salary'], 0) }}</td>
                </tr>
            </table>
        </td>

        <td>
            <h4>DEDUCTIONS</h4>
            <table class="data-table">
                <tr>
                    <td>Late Arrival</td>
                    <td class="text-right">{{ number_format($data['deductions']['late_deduction'], 0) }}</td>
                </tr>
                <tr>
                    <td>Early Leave</td>
                    <td class="text-right">{{ number_format($data['deductions']['early_leave_deduction'], 0) }}</td>
                </tr>
                <tr>
                    <td>Income Tax (PPh 21)</td>
                    <td class="text-right">{{ number_format($data['deductions']['tax_amount'], 0) }}</td>
                </tr>
                {{-- Spacer untuk menjaga keseimbangan visual --}}
                <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
                <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
                <tr class="total-row bold">
                    <td style="padding-top: 10px;">TOTAL DEDUCTIONS</td>
                    <td class="text-right">{{ number_format($data['deductions']['total_deduction'], 0) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="net-salary-box">
    <table>
        <tr class="bold">
            <td width="60%" style="font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Take Home Pay</td>
            <td class="text-right" style="font-size: 20px; color: #0f172a;">
                IDR {{ number_format($data['summary']['net_salary'], 0) }}
            </td>
        </tr>
    </table>
</div>

<div class="footer center">
    <p>This is a computer-generated document. No signature is required.</p>
    <small>{{ $company['footer'] ?? '' }}</small>
</div>

</body>
</html>
