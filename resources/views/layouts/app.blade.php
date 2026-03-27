<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'SIGI Dental EMR' }} — SIGI Dental EMR</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">

    {{-- Google Fonts: Plus Jakarta Sans --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    {{-- Remix Icon CDN --}}
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    {{-- Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    {{-- jQuery & DataTables CDN --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
    
    {{-- SweetAlert2 CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        :root {
            /* ... (keep existing root) ... */
        }
        
        /* ── DataTables Overrides ────────────────────────── */
        .dt-container { font-size: 13.5px; color: var(--text-body); }
        .dt-search input { 
            background: var(--bg-body) !important; border: 1px solid var(--border-color) !important; 
            border-radius: 8px !important; padding: 6px 12px !important; outline: none !important;
        }
        .dt-length select { 
            background: var(--bg-body) !important; border: 1px solid var(--border-color) !important; 
            border-radius: 8px !important; padding: 4px 8px !important; outline: none !important;
        }
        .dt-paging { margin-top: 16px !important; display: flex !important; justify-content: flex-end !important; gap: 4px !important; }
        .dt-paging-button { 
            border: 1px solid var(--border-color) !important; border-radius: 6px !important; 
            padding: 4px 12px !important; cursor: pointer !important; background: var(--bg-card) !important;
            transition: all .2s !important;
        }
        .dt-paging-button.current { background: #6691e7 !important; color: white !important; border-color: #6691e7 !important; }
        .dt-paging-button:hover:not(.current) { background: var(--icon-hover) !important; }
        table.dataTable { border-collapse: collapse !important; width: 100%; margin: 16px 0 !important; border-bottom: none !important; }
        table.dataTable thead th { 
            background: var(--bg-body); color: var(--text-heading); font-weight: 600; 
            text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;
            padding: 12px 16px !important; border-bottom: 1px solid var(--border-color) !important;
            white-space: nowrap;
        }
        table.dataTable td { padding: 12px 16px !important; border-bottom: 1px solid var(--border-color) !important; color: var(--text-body); white-space: nowrap; }
        table.dataTable tr:hover td { background: var(--icon-hover); }
        .dt-info { font-size: 13px; color: var(--text-muted); margin-top: 16px; }

        /* ── Velzon Original Components ─────────────────── */
        /* ── Modern Card Styling ────────────────────────── */
        .card { 
            background: var(--bg-card); 
            border-radius: 16px; 
            border: 1px solid var(--border-color); 
            box-shadow: 0 4px 6px -1px var(--shadow-color), 0 2px 4px -1px var(--shadow-color);
            margin-bottom: 24px; 
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            overflow: hidden;
            position: relative;
        }
        .card:hover {
            box-shadow: 0 10px 15px -3px var(--shadow-color), 0 4px 6px -2px var(--shadow-color);
        }
        .card-header { 
            padding: 1.25rem 1.5rem; 
            border-bottom: 1px solid var(--border-color); 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            background: transparent; 
        }
        .card-title { 
            font-size: 15px; 
            font-weight: 700; 
            color: var(--text-heading); 
            margin: 0; 
            letter-spacing: -0.01em;
        }
        .card-body { padding: 1.5rem; }
        
        /* Buttons */
        .btn { 
            display: inline-flex; align-items: center; gap: 4px; padding: 8px 16px; font-size: 13px; font-weight: 500; 
            border-radius: 4px; border: 1px solid transparent; transition: all 0.3s; cursor: pointer;
        }
        .btn-primary { background: #0ab39c; color: #fff; border-color: #0ab39c; box-shadow: 0 2px 4px rgba(10, 179, 156, 0.15); }
        .btn-primary:hover { background: #099885; border-color: #099885; }
        .btn-info { background: #45cbdf; color: #fff; border-color: #45cbdf; }
        .btn-danger { background: #f06548; color: #fff; border-color: #f06548; }
        .btn-light { background: #f3f6f9; color: #878a99; border-color: #f3f6f9; }
        .btn-light:hover { background: #e9ebec; color: #878a99; }

        /* ── Action Pill Colors (Tabs/Filters) ──────────────── */
        .active-pill-primary.active { background: rgba(64,81,137,0.1) !important; color: #405189 !important; box-shadow: 0 2px 4px rgba(64,81,137,0.05); }
        .active-pill-primary.active i { color: #405189; opacity: 1; }
        
        .active-pill-success.active { background: rgba(10,179,156,0.1) !important; color: #0ab39c !important; box-shadow: 0 2px 4px rgba(10,179,156,0.05); }
        .active-pill-success.active i { color: #0ab39c; opacity: 1; }
        
        .active-pill-danger.active { background: rgba(240,101,72,0.1) !important; color: #f06548 !important; box-shadow: 0 2px 4px rgba(240,101,72,0.05); }
        .active-pill-danger.active i { color: #f06548; opacity: 1; }
        
        .active-pill-warning.active { background: rgba(247,184,75,0.1) !important; color: #f7b84b !important; box-shadow: 0 2px 4px rgba(247,184,75,0.05); }
        .active-pill-warning.active i { color: #f7b84b; opacity: 1; }

        /* ── Badge & Subtle Utilities ───────────────────── */
        .badge { 
            display: inline-flex; align-items: center; gap: 5px; 
            padding: 4px 12px; font-size: 12px; font-weight: 600; 
            border-radius: 6px; line-height: 1.2;
        }
        .bg-success-subtle { background-color: rgba(10, 179, 156, 0.1) !important; color: #0ab39c !important; }
        .bg-warning-subtle { background-color: rgba(247, 184, 75, 0.15) !important; color: #a67018 !important; }
        .bg-danger-subtle { background-color: rgba(240, 101, 72, 0.1) !important; color: #f06548 !important; }
        .bg-info-subtle { background-color: rgba(41, 156, 219, 0.1) !important; color: #299cdb !important; }
        .bg-primary-subtle { background-color: rgba(64, 81, 137, 0.1) !important; color: #405189 !important; }

        .text-primary { color: #405189 !important; }
        .text-success { color: #0ab39c !important; }
        .text-info { color: #299cdb !important; }
        .text-warning { color: #f7b84b !important; }
        .text-danger { color: #f06548 !important; }
        .bg-warning-500 { background-color: #f7b84b !important; }
        .text-warning-600 { color: #f7b84b !important; }

        /* ── Modern Segmented Control (Tabs) ───────────── */
        .nav-pills-custom {
            display: inline-flex;
            background: #f3f6f9;
            padding: 4px;
            border-radius: 12px;
            gap: 4px;
            border: 1px solid #e9ecef;
            list-style: none;
            margin: 0;
        }

        .dark .nav-pills-custom {
            background: #2b3035;
            border-color: #343a40;
        }

        .nav-pills-custom .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            color: #878a99;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            background: transparent;
            cursor: pointer;
            text-decoration: none;
        }

        .nav-pills-custom .nav-link i {
            font-size: 16px;
            line-height: 1;
        }

        .nav-pills-custom .nav-link:hover {
            color: #405189;
            background: rgba(255, 255, 255, 0.5);
        }

        .dark .nav-pills-custom .nav-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        .nav-pills-custom .nav-link.active {
            background: #fff;
            color: #405189 !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        .dark .nav-pills-custom .nav-link.active {
            background: #343a40;
            color: #fff !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        /* Specific Status Colors for Active Segment */
        .nav-pills-custom .nav-link.active-pill-success.active {
            color: #0ab39c !important;
        }

        .nav-pills-custom .nav-link.active-pill-danger.active {
            color: #f06548 !important;
        }

        .nav-pills-custom .nav-link.active-pill-warning.active {
            color: #f7b84b !important;
        }

        :root {
            /* Light theme */
            --bg-body: #f3f6f9;
            --bg-card: #ffffff;
            --bg-nav: #ffffff;
            --text-heading: #343a40;
            --text-body: #495057;
            --text-muted: #878a99;
            --border-color: #e9ecef;
            --border-hover: #f1f3f5;
            --icon-hover: #f3f6f9;
            --shadow-color: rgba(56,65,74,.06);
            --dropdown-shadow: rgba(56,65,74,.12);
        }

        .dark {
            /* Dark theme */
            --bg-body: #1a1d21;
            --bg-card: #212529;
            --bg-nav: #212529;
            --text-heading: #ced4da;
            --text-body: #adb5bd;
            --text-muted: #878a99;
            --border-color: #2b3035;
            --border-hover: #343a40;
            --icon-hover: #2b3035;
            --shadow-color: rgba(0,0,0,.2);
            --dropdown-shadow: rgba(0,0,0,.3);
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--bg-body); 
            color: var(--text-body); 
            transition: background 0.2s, color 0.2s;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }

        /* ── Top Primary Bar (Premium & Colorful) ────────── */
        .topbar {
            background: linear-gradient(to right, #405189, #0ab39c) !important;
            border-bottom: none !important;
            height: 64px;
            display: flex;
            align-items: center;
            padding: 0 24px;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 1001;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .topbar-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .topbar-logo img { height: 36px; width: auto; filter: brightness(0) invert(1); } /* Force white logo */
        .topbar-search {
            flex: 1;
            max-width: 400px;
            position: relative;
        }
        .topbar-search input {
            width: 100%;
            background: rgba(255, 255, 255, 0.12) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 8px;
            padding: 8px 14px 8px 38px;
            font-size: 13.5px;
            color: #ffffff !important;
            outline: none;
            transition: all .2s;
        }
        .topbar-search input::placeholder { color: rgba(255, 255, 255, 0.5) !important; }
        .topbar-search input:focus { 
            background: rgba(255, 255, 255, 0.2) !important; 
            border-color: rgba(255, 255, 255, 0.3) !important;
            box-shadow: 0 0 0 3px rgba(255,255,255,.05); 
        }
        .topbar-search svg { position:absolute; left:11px; top:50%; transform:translateY(-50%); color: rgba(255, 255, 255, 0.6); }
        .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 8px; }
        .topbar-icon-btn {
            width: 38px; height: 38px; border-radius: 8px; border: none;
            background: transparent; cursor: pointer; display: flex; align-items: center; justify-content: center;
            color: rgba(255, 255, 255, 0.8) !important; transition: all .15s; position: relative;
        }
        .topbar-icon-btn:hover { background: rgba(255, 255, 255, 0.1); color: #ffffff !important; }
        .topbar-badge {
            position: absolute; top: 5px; right: 5px;
            width: 8px; height: 8px; border-radius: 50%;
            background: #f06548; border: 2px solid #405189;
        }
        .topbar-avatar {
            width: 38px; height: 38px; border-radius: 10px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.15);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 13px; font-weight: 700;
            cursor: pointer; margin-left: 4px; transition: all .2s;
        }
        .topbar-avatar:hover { background: rgba(255, 255, 255, 0.3); transform: scale(1.05); }
        .topbar-divider { width:1px; height:24px; background: rgba(255, 255, 255, 0.15); margin:0 4px; }

        .module-nav {
            background: var(--bg-nav);
            border-bottom: 1px solid var(--border-color);
            padding: 0 24px;
            display: flex;
            align-items: center;
            gap: 2px;
            height: 46px;
            position: sticky;
            top: 64px;
            z-index: 99;
            /* overflow-x: auto; Removed because it clips dropdown menus */
        }
        /* Mobile scroll removed to avoid dropdown clipping, but we can allow flex wrap or scrolling if needed later */
        .module-nav a {
            display: flex; align-items: center; gap: 6px;
            padding: 0 14px;
            height: 46px;
            font-size: 13px; font-weight: 500;
            color: var(--text-muted);
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: color .15s, border-color .15s;
            white-space: nowrap;
        }
        .module-nav a:hover { color: var(--text-heading); }
        .module-nav a.active { color: #6691e7; border-bottom-color: #6691e7; font-weight: 600; }
        .module-nav a svg { flex-shrink: 0; }
        
        @media (max-width: 768px) {
            .module-nav {
                position: fixed;
                bottom: 16px;
                top: auto;
                left: 16px;
                right: 16px;
                height: 64px;
                padding: 0 8px;
                border: 1px solid var(--border-color);
                border-radius: 20px;
                justify-content: space-around;
                background-color: var(--bg-card);
                box-shadow: 0 10px 25px -5px var(--shadow-color), 0 8px 10px -6px rgba(0,0,0,0.1);
                z-index: 1000;
                gap: 4px;
            }
            .module-nav > a, .module-nav > div {
                flex: 1;
                height: 60px;
            }
            .module-nav > a, .module-nav > div > a.flex {
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 4px;
                height: 100%;
                width: 100%;
                padding: 0;
                border-bottom: none;
                border-top: 2px solid transparent;
            }
            .module-nav > a span, .module-nav > div > a.flex span,
            .module-nav > a svg, .module-nav > div > a.flex svg { 
                display: none; 
            }
            .module-nav > a i, .module-nav > div > a.flex i { 
                font-size: 20px !important; 
                margin: 0 auto !important; 
            }
            /* Submenu dropdown positioning: upwards and full width on mobile */
            .module-nav .absolute {
                top: auto !important;
                bottom: calc(100% + 6px) !important;
                left: 12px !important;
                right: 12px !important;
                width: auto !important;
                margin: 0 !important;
            }
            .page-content { padding: 16px; padding-bottom: 100px; overflow-x: hidden; }
            .topbar { padding: 0 16px; gap: 8px; }
            .topbar-logo { min-width: auto; }
            .topbar-logo img { height: 28px; }
            .topbar-right { gap: 2px; }
            .topbar-search { display: none; } /* Hide search on mobile to save topbar space */
            .stat-grid, .charts-grid, .bottom-grid { gap: 16px !important; margin-bottom: 16px !important; }
            .card-body { padding: 16px !important; }
            .card-header { padding: 16px 16px 0 !important; }
            .card-title { font-size: 13px !important; }
        }

        /* ── User Dropdown ───────────────────────────────── */
        .dropdown { position: relative; }
        .dropdown-menu {
            position: absolute; right: 0; top: calc(100% + 8px);
            background: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: 10px; min-width: 200px;
            box-shadow: 0 8px 24px var(--dropdown-shadow);
            padding: 6px 0; z-index: 200;
        }
        .dropdown-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 16px; font-size: 13px; color: var(--text-heading);
            cursor: pointer; text-decoration: none;
            transition: background .12s;
        }
        .dropdown-item:hover { background: var(--icon-hover); }
        .dropdown-item svg { color: var(--text-muted); }
        .dropdown-divider { height: 1px; background: var(--border-color); margin: 4px 0; }

        /* ── Page Content ────────────────────────────────── */
        .page-content { padding: 24px; max-width: 100vw; overflow-x: hidden; flex: 1; }
        /* ── Dashboard Specific Stat Cards ──────────────── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .stat-card {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 4px;
        }
        .stat-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .stat-value {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-heading);
            line-height: 1;
        }
        .stat-trend {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .stat-trend.up { color: #10b981; }
        .stat-trend.down { color: #ef4444; }
        .stat-trend .label { color: var(--text-muted); font-weight: 400; margin-left: 2px; }

        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        @media (max-width: 1024px) { .charts-grid { grid-template-columns: 1fr; } }

        .chart-wrap { position: relative; width: 100%; height: 300px; }

        /* ── Bottom grid ─────────────────────────────────── */
        .bottom-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 900px) { .bottom-grid { grid-template-columns: 1fr; } }

        /* ── Bottom grid ─────────────────────────────────── */
        .bottom-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 900px) { .bottom-grid { grid-template-columns: 1fr; } }

        /* ── Activity list ───────────────────────────────── */
        .activity-item { display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--border-hover); }
        .activity-item:last-child { border-bottom: none; }
        .activity-dot {
            width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; color: #fff;
        }
        .activity-meta { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

        /* ── Modern Page Header ─────────────────────────── */
        .page-header { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            margin-bottom: 28px; 
            padding: 0 4px;
        }
        .page-header-title { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
        }
        .page-header-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #fff;
            border: 1px solid var(--border-hover);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #405189;
            font-size: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            transition: all 0.2s;
        }
        .page-header-icon i { line-height: 1; }
        .page-header:hover .page-header-icon { transform: translateY(-1px); box-shadow: 0 4px 8px rgba(0,0,0,0.06); }

        .page-header-title h1 { 
            font-size: 24px; 
            font-weight: 800; 
            color: var(--text-heading); 
            margin: 0; 
            letter-spacing: -0.02em;
        }
        .page-header-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
        }
        .page-header-breadcrumb a { color: var(--text-muted); text-decoration: none; transition: color .15s; }
        .page-header-breadcrumb a:hover { color: #6691e7; }
        .page-header-breadcrumb .sep { color: var(--border-color); font-weight: 300; }
        
        @media (max-width: 768px) {
            .page-header { flex-direction: column; align-items: flex-start; gap: 12px; }
            .page-header-icon { width: 36px; height: 36px; font-size: 18px; }
            .page-header-title h1 { font-size: 20px; }
        }

        /* ── Modern Table Styles ─────────────────────────── */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        table.display, 
        table.data-table,
        .table-custom {
            width: 100% !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            margin: 0 !important;
        }
        
        table.display thead th,
        table.data-table thead th,
        .table-custom thead th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
            padding: 12px 16px;
            border-top: 1px solid #eff2f7;
            border-bottom: 1px solid #eff2f7;
            text-align: left;
        }
        
        table.display tbody td,
        table.data-table tbody td,
        .table-custom tbody td {
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f5;
            color: #495057;
            font-size: 13.5px;
            transition: background-color 0.15s;
        }
        
        table.display tbody tr:hover td,
        table.data-table tbody tr:hover td,
        .table-custom tbody tr:hover td {
            background-color: #f8f9fa;
        }

        /* ── DataTables Specific Overrides ──────────────── */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #878a99 !important;
            font-size: 13px;
            padding: 15px !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 5px 12px !important;
            margin-left: 4px !important;
            border-radius: 6px !important;
            border: 1px solid #e9ecef !important;
            background: #fff !important;
            color: #495057 !important;
            font-weight: 600 !important;
            transition: all 0.2s;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: #405189 !important;
            color: #fff !important;
            border-color: #405189 !important;
            box-shadow: 0 2px 4px rgba(64, 81, 137, 0.2);
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f3f6f9 !important;
            color: #405189 !important;
            border-color: #d1d9e4 !important;
            transform: translateY(-1px);
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 4px 8px;
            margin: 0 4px;
            outline: none;
            cursor: pointer;
        }

        /* ── App Footer ──────────────────────────────────── */
        .app-footer {
            padding: 16px 24px; font-size: 13px; color: var(--text-muted);
            border-top: 1px solid var(--border-color); background: var(--bg-nav);
            display: flex; align-items: center; justify-content: space-between;
            margin-top: 24px;
        }
        @media (max-width: 768px) {
            .app-footer {
                padding: 16px; padding-bottom: 76px;
                flex-direction: column; gap: 6px; text-align: center;
                margin-top: 0;
            }
        }
        /* ── Global Loader ───────────────────────────────── */
        .global-loader {
            position: fixed; inset: 0; z-index: 99999;
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center;
        }
        .dark .global-loader { background: rgba(26,29,33,0.8); }
        .loader-content { display: flex; flex-direction: column; align-items: center; gap: 12px; }
        .tooth-spinner {
            width: 48px; height: 48px; color: #6691e7;
            animation: pulseTooth 1s ease-in-out infinite;
        }
        @keyframes pulseTooth {
            0%   { transform: scale(0.9); opacity: 0.7; }
            50%  { transform: scale(1.15); opacity: 1; }
            100% { transform: scale(0.9); opacity: 0.7; }
        }
        .loader-text {
            font-size: 14px; font-weight: 600; color: #6691e7;
            letter-spacing: 1px; animation: pulseText 1s ease-in-out infinite;
        }
        @keyframes pulseText {
            0%   { opacity: 0.5; }
            50%  { opacity: 1; }
            100% { opacity: 0.5; }
        }
    </style>
</head>
<body x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" 
      x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val));" 
      :class="darkMode ? 'dark' : ''">

{{-- ════════════════════════════════════════════════════════ --}}
{{-- GLOBAL LOADING OVERLAY --}}
{{-- ════════════════════════════════════════════════════════ --}}
<div x-data="{ loading: false }" 
     x-on:beforeunload.window="loading = true"
     x-on:livewire:navigating.window="loading = true"
     x-on:livewire:navigated.window="loading = false"
     x-on:livewire-upload-start.window="loading = true"
     x-on:livewire-upload-finish.window="loading = false"
     x-show="loading"
     x-transition.opacity.duration.300ms
     style="display:none;"
     class="global-loader">
    <div class="loader-content">
        <svg class="tooth-spinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2.26 10c.85-6.79 5-8 9.74-8s8.89 1.21 9.74 8c.55 4.39-1.32 8.52-4.14 11.2a2 2 0 0 1-2.82-.12l-2-2.13a1 1 0 0 0-1.46 0l-2 2.13a2 2 0 0 1-2.82.12C3.58 18.52 1.71 14.39 2.26 10Z"/>
            <path d="M12 11v11"/>
        </svg>
        <div class="loader-text">Memuat...</div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════ --}}
{{-- TOP PRIMARY BAR --}}
{{-- ════════════════════════════════════════════════════════ --}}
<header class="topbar">
    <a href="{{ route('dashboard.index') }}" class="topbar-logo">
        {{-- Use logo with brightness/invert filter in CSS for maximum contrast --}}
        <img src="{{ asset('images/sigi-logo-white.svg') }}" alt="SIGI Dental EMR">
    </a>

    <div class="topbar-divider"></div>

    {{-- Search --}}
    <div class="topbar-search">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" placeholder="Cari pasien, jadwal, rekam medis…">
    </div>

    <div class="topbar-right">
        {{-- Theme Toggle --}}
        <button class="topbar-icon-btn" title="Ganti Tema" @click="darkMode = !darkMode">
            {{-- Sun icon (shown in dark mode) --}}
            <svg x-show="darkMode" x-cloak xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="M4.93 4.93l1.41 1.41"/><path d="M17.66 17.66l1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="M4.93 19.07l1.41-1.41"/><path d="M17.66 6.34l1.41-1.41"/></svg>
            {{-- Moon icon (shown in light mode) --}}
            <svg x-show="!darkMode" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
        </button>

        {{-- Notifications --}}
        <button class="topbar-icon-btn" title="Notifikasi">
            <span class="topbar-badge"></span>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        </button>

        {{-- Settings --}}
        <button class="topbar-icon-btn" title="Pengaturan">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        </button>

        <div class="topbar-divider"></div>

        {{-- Avatar + Dropdown --}}
        <div class="dropdown" x-data="{ open: false }" @click.outside="open = false">
            <div class="topbar-avatar" @click="open = !open" title="Admin">AD</div>
            <div class="dropdown-menu" x-show="open" x-cloak x-transition style="display:none;">
                <div class="dropdown-item" style="flex-direction:column;align-items:flex-start;gap:1px;padding-bottom:12px;">
                    <span style="font-weight:600;color:#343a40;">Admin System</span>
                    <span style="font-size:12px;color:#878a99;">admin@sigidental.id</span>
                </div>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Profil Saya
                </a>
                <a href="#" class="dropdown-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Pengaturan
                </a>
                <div class="dropdown-divider"></div>
                <a href="{{ route('logout') }}" class="dropdown-item" style="color:#ef4444;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Keluar
                </a>
            </div>
        </div>
    </div>
</header>

{{-- ════════════════════════════════════════════════════════ --}}
{{-- SECONDARY HORIZONTAL MODULE NAV --}}
{{-- ════════════════════════════════════════════════════════ --}}
<livewire:layouts.dynamic-menu />

{{-- ════════════════════════════════════════════════════════ --}}
{{-- PAGE BODY --}}
{{-- ════════════════════════════════════════════════════════ --}}
<main class="page-content">
    {{ $slot }}
</main>

{{-- ════════════════════════════════════════════════════════ --}}
{{-- APP FOOTER --}}
{{-- ════════════════════════════════════════════════════════ --}}
<footer class="app-footer">
    <div class="footer-left">
        &copy; {{ date('Y') }} PT. BTI. All rights reserved.
    </div>
    <div class="footer-right">
        SIGI Dental EMR v1.0
    </div>
</footer>

    @livewireScripts
    <script>
        document.addEventListener('livewire:navigated', () => {
            window.addEventListener('alert', event => {
                let data = event.detail;
                if (Array.isArray(data)) data = data[0];
                
                Swal.fire({
                    icon: data.type,
                    title: data.type === 'success' ? 'Berhasil!' : 'Perhatian',
                    text: data.message,
                    timer: data.type === 'success' ? 3000 : null,
                    showConfirmButton: data.type !== 'success',
                    confirmButtonColor: '#405189'
                });
            });
        });

        // Initial load listener
        window.addEventListener('alert', event => {
            let data = event.detail;
            if (Array.isArray(data)) data = data[0];
            
            Swal.fire({
                icon: data.type,
                title: data.type === 'success' ? 'Berhasil!' : 'Perhatian',
                text: data.message,
                timer: data.type === 'success' ? 3000 : null,
                showConfirmButton: data.type !== 'success',
                confirmButtonColor: '#405189'
            });
        });
    </script>
</body>
</html>
