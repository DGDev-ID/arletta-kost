<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $transaction['order_id'] }}</title>
    <style>
        /* ── Page Setup ──────────────── */
        @page {
            margin: 1.5cm 1.5cm;
        }

        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 9pt;
            color: #333333;
            background: #ffffff;
            line-height: 1.4;
        }

        /* ── Reset Utility ──────────────── */
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 0; margin: 0; vertical-align: top; }

        /* ── Header (Kop Surat) ──────────────────────── */
        .header-table {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }
        
        .brand { 
            font-size: 26pt; 
            font-weight: 800; 
            color: #0f172a; 
            letter-spacing: -0.5px; 
            line-height: 1; 
            margin-bottom: 8px; 
        }
        
        .kop-address { 
            font-size: 9.5pt; 
            color: #475569; 
            line-height: 1.4; 
        }
        .kop-address strong { 
            font-size: 11pt; 
            color: #1e293b; 
        }

        .inv-label { 
            font-size: 30pt; 
            font-weight: 900; 
            color: #000000ff; 
            /* color: #2563eb;  */
            letter-spacing: 1.5px; 
            line-height: 1; 
            text-transform: uppercase; 
        }

        /* ── Meta & Status (Bawah Garis) ──────────────── */
        .meta-status-container {
            margin-bottom: 15px;
        }

        .meta-table td { padding: 4px 0; font-size: 9.5pt; }
        .meta-lbl { color: #64748b; width: 110px; }
        .meta-val { color: #0f172a; font-weight: bold; }

        .status-pill {
            display: block;
            padding: 8px 18px;
            border-radius: 4px;
            font-size: 8.5pt;
            font-weight: bold;
            text-align: center;
            line-height: 1.5;
            white-space: nowrap; /* Mencegah teks turun ke bawah secara otomatis */
        }
        .s-success { background: #ecfdf5; color: #065f46; border: 1px solid #34d399; }
        .s-pending { background: #fffbeb; color: #92400e; border: 1px solid #fbbf24; }
        .s-failed  { background: #fef2f2; color: #991b1b; border: 1px solid #f87171; }

        /* ── Layout 2 Kolom (Card) ───────────────────────── */
        .card-container { margin-bottom: 20px; }
        .card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
            background: #fafafa;
        }
        .card-title {
            font-size: 9pt;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }
        .inner-table td { padding-bottom: 4px; font-size: 9pt; }
        .lbl { width: 35%; color: #6b7280; }
        .val { font-weight: bold; color: #111827; }

        /* ── Tabel Tagihan ─────────────────────────────── */
        .sec-head {
            font-size: 10pt;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .items { margin-bottom: 20px; }
        .items th {
            background: #f3f4f6;
            padding: 8px;
            font-size: 9pt;
            border-bottom: 2px solid #d1d5db;
            text-align: left;
        }
        .items th.r, .items td.r { text-align: right; }
        .items td { padding: 8px; border-bottom: 1px solid #e5e7eb; font-size: 9pt; }
        .item-sub { font-size: 8pt; color: #6b7280; margin-top: 2px; }

        /* ── Footer ──────────────────────────────────── */
        .footer {
            border-top: 1px dashed #d1d5db;
            padding-top: 15px;
            text-align: center;
            margin-top: 30px;
        }
        .foot-main { font-size: 9pt; font-weight: bold; color: #111827; margin-bottom: 3px; }
        .foot-sub { font-size: 8pt; color: #9ca3af; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 60%; vertical-align: middle;">
                <table style="width: auto; border-collapse: collapse;">
                    <tr>
                        @php
                            $iconPath = public_path('images/arletta-kost-icon.png');
                            $iconBase64 = base64_encode(file_get_contents($iconPath));
                            $iconSrc = 'data:image/png;base64,' . $iconBase64;
                        @endphp
                        <td style="vertical-align: middle; padding-right: 10px;">
                            <img src="{{ $iconSrc }}" style="height: 48px; width: 48px; object-fit: contain; display: block;">
                        </td>
                        <td style="vertical-align: middle;">
                            <div class="brand">Arletta Kost</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: middle;">
                <div class="inv-label">INVOICE</div>
            </td>
        </tr>
    </table>

    @php
        $sc = match($transaction['status']) { 'success'=>'s-success', 'pending'=>'s-pending', default=>'s-failed' };
        
        /* Menggunakan &nbsp; (non-breaking space) agar kata tidak terpisah oleh DomPDF */
        $sl = match($transaction['status']) {
            'success' => 'SUKSES<br>PEMBAYARAN&nbsp;BERHASIL',
            'pending' => 'MENUNGGU<br>PEMBAYARAN',
            'failed'  => 'GAGAL<br>PEMBAYARAN&nbsp;GAGAL',
            default   => strtoupper($transaction['status']),
        };
    @endphp

    <table class="meta-status-container">
        <tr>
            <td style="width: 50%; vertical-align: middle;">
                <table class="meta-table">
                    <tr>
                        <td class="meta-lbl">No. Invoice</td>
                        <td class="meta-val">: {{ $transaction['order_id'] }}</td>
                    </tr>
                    <tr>
                        <td class="meta-lbl">Tanggal Terbit</td>
                        <td class="meta-val">: {{ \Carbon\Carbon::parse($transaction['created_at'])->format('d M Y, H:i') }}</td>
                    </tr>
                </table>
            </td>
            
            <td style="width: 50%; text-align: right; vertical-align: middle;">
                <table align="right" style="width: 1%;"> 
                    <tr><td class="status-pill {{ $sc }}">{!! $sl !!}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="card-container">
        <tr>
            <td style="width: 48%;">
                <div class="card">
                    <div class="card-title">Informasi Tenant</div>
                    <table class="inner-table">
                        <tr><td class="lbl">Nama</td><td class="val">{{ $tenant['name'] }}</td></tr>
                        <tr><td class="lbl">No. HP</td><td class="val">{{ $tenant['phone_number'] }}</td></tr>
                        @if(!empty($tenant['email']))
                        <tr><td class="lbl">Email</td><td class="val">{{ $tenant['email'] }}</td></tr>
                        @endif
                    </table>
                </div>
            </td>
            <td style="width: 4%;"></td> 
            <td style="width: 48%;">
                <div class="card">
                    <div class="card-title">Informasi Kamar</div>
                    <table class="inner-table">
                        <tr><td class="lbl">No. Kamar</td><td class="val">{{ $room['room_number'] }}</td></tr>
                        <tr><td class="lbl">Kategori</td><td class="val">{{ $room['category_name'] }}</td></tr>
                        <tr><td class="lbl">Mulai Sewa</td><td class="val">{{ $bill['start_date'] }}</td></tr>
                        <tr><td class="lbl">Akhir Sewa</td><td class="val">{{ $bill['due_date'] }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="sec-head">Rincian Tagihan</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 40%;">Deskripsi</th>
                <th style="width: 30%;">Keterangan</th>
                <th style="width: 25%;" class="r">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>
                    <strong>Sewa Kamar {{ $room['room_number'] }}</strong>
                    <div class="item-sub">{{ $room['category_name'] }} — {{ $kost['name'] }}</div>
                </td>
                <td>{{ $bill['start_date'] }} s/d {{ $bill['due_date'] }}</td>
                <td class="r">{{ $bill['total_price_formatted'] }}</td>
            </tr>

            @if($bill['payment_scheme'] === 'dp')
            <tr>
                <td>2</td>
                <td>
                    <strong>Down Payment (50%)</strong>
                    <div class="item-sub">Pembayaran pertama</div>
                </td>
                <td>Skema DP</td>
                <td class="r">{{ $bill['dp_amount_formatted'] }}</td>
            </tr>
            @endif

            @if($transaction['transaction_fee'] > 0)
            <tr>
                <td>{{ $bill['payment_scheme'] === 'dp' ? 3 : 2 }}</td>
                <td>
                    <strong>Biaya Transaksi</strong>
                    <div class="item-sub">{{ ucfirst($transaction['payment_type']) }}</div>
                </td>
                <td>Gateway fee</td>
                <td class="r">{{ $transaction['fee_formatted'] }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <table class="card-container">
        <tr>
            <td style="width: 48%;">
                <div class="card">
                    <div class="card-title">Info Pembayaran</div>
                    <table class="inner-table">
                        <tr><td class="lbl">Metode</td><td class="val">{{ ucfirst($transaction['payment_type']) }}</td></tr>
                        @if($transaction['midtrans_method'])
                        <tr><td class="lbl">Tipe</td><td class="val">{{ strtoupper($transaction['midtrans_method']) }}</td></tr>
                        @endif
                        <tr><td class="lbl">Skema</td><td class="val">{{ $bill['payment_scheme'] === 'dp' ? 'DP (50%)' : 'Full Payment' }}</td></tr>
                    </table>
                </div>
            </td>
            <td style="width: 4%;"></td> 
            <td style="width: 48%;">
                <div class="card">
                    <div class="card-title">Ringkasan</div>
                    <table class="inner-table">
                        @if($bill['payment_scheme'] === 'dp')
                        <tr><td class="lbl">Total Sewa</td><td class="val" style="text-align: right;">{{ $bill['total_price_formatted'] }}</td></tr>
                        <tr><td class="lbl">DP (50%)</td><td class="val" style="text-align: right;">{{ $bill['dp_amount_formatted'] }}</td></tr>
                        @else
                        <tr><td class="lbl">Subtotal</td><td class="val" style="text-align: right;">{{ $bill['total_price_formatted'] }}</td></tr>
                        @endif

                        @if($transaction['transaction_fee'] > 0)
                        <tr><td class="lbl">Biaya Admin</td><td class="val" style="text-align: right;">{{ $transaction['fee_formatted'] }}</td></tr>
                        @endif
                        
                        <tr>
                            <td colspan="2" style="border-top: 1px solid #ccc; padding-top: 8px; margin-top: 5px;"></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #111827; font-size: 10pt;">TOTAL BAYAR</td>
                            <td style="font-weight: bold; color: #2563eb; text-align: right; font-size: 11pt;">{{ $transaction['total_price_formatted'] }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        <div class="foot-main">Terima kasih telah mempercayai Arletta Kost</div>
        <div class="foot-sub">
            Dokumen ini diterbitkan secara otomatis &nbsp;·&nbsp; Invoice {{ $transaction['order_id'] }} &nbsp;·&nbsp; {{ now()->format('d M Y, H:i') }} WIB
        </div>
    </div>

</body>
</html>