<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* Tipografi & Dasar */
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        h2 { margin: 5px 0; color: #1a1a1a; text-transform: uppercase; letter-spacing: 1px; }
        h4 {
            margin: 0 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #444;
            font-size: 12px;
            color: #2c3e50;
        }

        /* Layout Tabel */
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        td { padding: 8px 5px; vertical-align: top; }

        /* Utilitas */
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .center { text-align: center; }
        .uppercase { text-transform: uppercase; }

        /* Header Box */
        .header-container { margin-bottom: 30px; border-bottom: 3px double #eee; padding-bottom: 10px; }

        /* Kolom Berdampingan (Earnings & Deductions) */
        .split-section td { width: 50%; padding: 0 10px; border: none; }

        /* Styling Baris Tabel Data */
        .data-table tr { border-bottom: 1px solid #f2f2f2; }
        .data-table tr.total-row { background-color: #f9f9f9; border-bottom: 2px solid #333; }

        /* Net Salary Box */
        .net-salary-box {
            background-color: #2c3e50;
            color: white;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }
        .net-salary-box td { border: none !important; color: white; font-size: 14px; }

        /* Footer */
        .footer { margin-top: 50px; color: #7f8c8d; font-style: italic; }
    </style>
</head>
<body>

{{-- HEADER UTAMA --}}
<div class="header-container">
    <table style="margin-bottom: 0;">
        <tr>
            <td width="50%">
                @if(!empty($company['logo']))
                    <img src="{{ public_path(parse_url($company['logo'], PHP_URL_PATH)) }}" height="50">
                @endif
                <h2>{{ $company['site_name'] ?? 'COMPANY NAME' }}</h2>
                <p style="margin: 0; font-size: 10px;">Official Payslip</p>
            </td>
            <td width="50%" class="text-right">
                <div style="font-size: 14px; font-weight: bold; color: #e74c3c;">SLIP GAJI / PAYSLIP</div>
                <div style="margin-top: 5px;">ID: {{ $data['uuid'] }}</div>
                <div>Status: <span style="color: green;">{{ $data['status']['label'] }}</span></div>
            </td>
        </tr>
    </table>
</div>

{{-- INFORMASI KARYAWAN & PERIODE --}}
<table>
    <tr>
        <td width="50%">
            <h4>KARYAWAN</h4>
            <table class="data-table">
                <tr><td>Nama</td><td class="bold">: {{ $data['employee']['name'] }}</td></tr>
                <tr><td>NIK</td><td>: {{ $data['employee']['nik'] }}</td></tr>
                <tr><td>Jabatan</td><td>: {{ $data['employee']['position']['name'] }}</td></tr>
                <tr><td>Status Kerja</td><td>: {{ $data['employee']['employment_status'] }}</td></tr>
            </table>
        </td>
        <td width="50%">
            <h4>PERIODE</h4>
            <table class="data-table">
                <tr><td>Mulai</td><td>: {{ $data['period']['start'] }}</td></tr>
                <tr><td>Selesai</td><td>: {{ $data['period']['end'] }}</td></tr>
                <tr><td>Hari Kerja</td><td>: {{ $data['period']['days'] }} Hari</td></tr>
                <tr><td>Tanggal Cetak</td><td>: {{ $data['finalized_at'] }}</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- RINCIAN GAJI & POTONGAN --}}
<table class="split-section">
    <tr>
        <td>
            <h4>PENDAPATAN (EARNINGS)</h4>
            <table class="data-table">
                <tr>
                    <td>Gaji Pokok</td>
                    <td class="text-right">{{ number_format($data['earnings']['base_salary'], 2) }}</td>
                </tr>
                @foreach($data['earnings']['allowances'] ?? [] as $allowance)
                    <tr>
                        <td>{{ $allowance['name'] }}</td>
                        <td class="text-right">{{ number_format($allowance['amount'], 2) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td>Lembur (Overtime)</td>
                    <td class="text-right">{{ number_format($data['earnings']['overtime_pay'], 2) }}</td>
                </tr>
                <tr>
                    <td>Penyesuaian</td>
                    <td class="text-right">{{ number_format($data['earnings']['manual_adjustment'], 2) }}</td>
                </tr>
                <tr class="total-row bold">
                    <td>TOTAL PENDAPATAN KOTOR</td>
                    <td class="text-right">{{ number_format($data['earnings']['gross_salary'], 2) }}</td>
                </tr>
            </table>
        </td>

        <td>
            <h4>POTONGAN (DEDUCTIONS)</h4>
            <table class="data-table">
                <tr>
                    <td>Keterlambatan</td>
                    <td class="text-right">{{ number_format($data['deductions']['late_deduction'], 2) }}</td>
                </tr>
                <tr>
                    <td>Pulang Awal</td>
                    <td class="text-right">{{ number_format($data['deductions']['early_leave_deduction'], 2) }}</td>
                </tr>
                <tr>
                    <td>Pajak (PPh 21)</td>
                    <td class="text-right">{{ number_format($data['deductions']['tax_amount'], 2) }}</td>
                </tr>
                {{-- Spacer untuk menjaga keseimbangan visual --}}
                <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
                <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
                <tr class="total-row bold">
                    <td>TOTAL POTONGAN</td>
                    <td class="text-right">{{ number_format($data['deductions']['total_deduction'], 2) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- TOTAL BERSIH --}}
<div class="net-salary-box">
    <table>
        <tr class="bold">
            <td width="70%" style="font-size: 16px;">GAJI BERSIH (TAKE HOME PAY)</td>
            <td class="text-right" style="font-size: 18px;">
                IDR {{ number_format($data['summary']['net_salary'], 2) }}
            </td>
        </tr>
    </table>
</div>

<div class="footer center">
    <p>Ini adalah dokumen elektronik yang dihasilkan secara otomatis dan tidak memerlukan tanda tangan basah.</p>
    <small>{{ $company['footer'] ?? '' }}</small>
</div>

</body>
</html>
