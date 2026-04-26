<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>@yield('title', 'Laporan')</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 9pt; color: #333; margin: 0; padding: 10px; }
        .kop-surat { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; position: relative; }
        .kop-surat h1 { margin: 0; font-size: 18pt; text-transform: uppercase; color: #1a202c; letter-spacing: 1px; }
        .kop-surat p { margin: 2px 0; font-size: 9pt; color: #4a5568; }
        .kop-surat .info { margin-top: 5px; font-style: italic; }
        
        .logo-container { margin-bottom: 5px; }
        .logo { height: 60px; }

        .content { margin-top: 20px; }
        
        /* Common PDF Styles */
        .page-break { page-break-after: always; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .mt-4 { margin-top: 1rem; }
        .mb-4 { margin-bottom: 1rem; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background: #f7fafc; padding: 8px; border: 1px solid #e2e8f0; text-align: left; font-size: 8pt; color: #4a5568; }
        td { padding: 8px; border: 1px solid #e2e8f0; font-size: 8.5pt; }
        
        .footer { position: fixed; bottom: -30px; left: 0; right: 0; height: 30px; text-align: center; font-size: 8pt; color: #a0aec0; border-top: 1px solid #edf2f7; padding-top: 5px; }
    </style>
    @yield('styles')
</head>
<body>
    <div class="kop-surat">
        @if(isset($instansi) && $instansi->logo)
            <div class="logo-container">
                <img src="{{ public_path('storage/' . $instansi->logo) }}" class="logo">
            </div>
        @endif
        <h1>{{ $instansi->nama_instansi ?? 'SIGI DENTAL CLINIC' }}</h1>
        <p>{{ $instansi->alamat ?? 'Alamat Instansi Belum Diatur' }}</p>
        <p class="info">
            Telp: {{ $instansi->telepon ?? '-' }} 
            @if($instansi && $instansi->email) | Email: {{ $instansi->email }} @endif
            @if($instansi && $instansi->website) | Website: {{ $instansi->website }} @endif
        </p>
    </div>

    <div class="content">
        @yield('content')
    </div>

    <div class="footer">
        Dicetak pada: {{ date('d/m/Y H:i') }} | {{ $instansi->nama_instansi ?? 'SIGI Dental EMR' }}
    </div>
</body>
</html>
