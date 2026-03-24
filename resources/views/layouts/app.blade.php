<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'StockFlow') — StockFlow</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet" />

    <style>
        /* ── Reset & Base ─────────────────────────────────────────────────── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg: #0D0F14;
            --surface: #13161D;
            --card: #181C25;
            --border: #252A36;
            --border-hi: #323848;
            --accent: #6C8EF5;
            --accent-dim: #3D5199;
            --accent-glow: rgba(108, 142, 245, .15);
            --green: #3DD68C;
            --green-dim: #1A4A34;
            --amber: #F5A623;
            --amber-dim: #4A3A10;
            --red: #F05252;
            --red-dim: #4A1515;
            --slate: #8892A4;
            --text: #E8EBF2;
            --text-mid: #A8B2C4;
            --font-display: 'DM Serif Display', Georgia, serif;
            --font-body: 'DM Sans', system-ui, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
            --sidebar-w: 220px;
            --topbar-h: 64px;
            --radius: 10px;
            --radius-sm: 6px;
        }

        html {
            font-size: 14px;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: var(--font-body);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── Scrollbar ────────────────────────────────────────────────────── */
        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        ::-webkit-scrollbar-track {
            background: var(--surface);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 2px;
        }

        /* ── Typography ───────────────────────────────────────────────────── */
        h1,
        h2,
        h3,
        h4 {
            font-weight: 500;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* ── Forms ────────────────────────────────────────────────────────── */
        input,
        select,
        textarea {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
            font-family: var(--font-body);
            font-size: 13px;
            border-radius: var(--radius-sm);
            padding: 9px 12px;
            outline: none;
            width: 100%;
            transition: border-color .15s, box-shadow .15s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        input::placeholder,
        textarea::placeholder {
            color: var(--slate);
        }

        label {
            font-size: 12px;
            color: var(--slate);
            display: block;
            margin-bottom: 5px;
        }

        /* ── Buttons ──────────────────────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            font-family: var(--font-body);
            border: none;
            cursor: pointer;
            transition: all .15s;
            text-decoration: none;
        }

        .btn:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
        }

        .btn-primary:hover {
            background: #7d9cf7;
        }

        .btn-ghost {
            background: transparent;
            color: var(--text-mid);
            border: 1px solid var(--border);
        }

        .btn-ghost:hover {
            background: var(--surface);
        }

        .btn-danger {
            background: var(--red-dim);
            color: var(--red);
            border: 1px solid rgba(240, 82, 82, .2);
        }

        .btn-danger:hover {
            background: #5a1a1a;
        }

        .btn-success {
            background: var(--green-dim);
            color: var(--green);
            border: 1px solid rgba(61, 214, 140, .2);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-block {
            width: 100%;
            justify-content: center;
            padding: 11px;
        }

        /* ── Cards ────────────────────────────────────────────────────────── */
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .card-header h3 {
            font-size: 14px;
            font-weight: 500;
        }

        .card-header p {
            font-size: 11px;
            color: var(--slate);
            margin-top: 2px;
        }

        .card-body {
            padding: 20px;
        }

        /* ── Table ────────────────────────────────────────────────────────── */
        .table-wrap {
            overflow-x: auto;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            font-weight: 500;
            color: var(--slate);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: 10px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover td {
            background: rgba(255, 255, 255, .018);
        }

        tbody tr {
            transition: background .1s;
        }

        /* ── Badges / Tags ────────────────────────────────────────────────── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }

        .badge::before {
            content: '';
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: currentColor;
        }

        .badge-pending {
            background: rgba(245, 166, 35, .15);
            color: var(--amber);
        }

        .badge-processing {
            background: var(--accent-glow);
            color: var(--accent);
        }

        .badge-completed {
            background: rgba(61, 214, 140, .15);
            color: var(--green);
        }

        .badge-cancelled {
            background: rgba(240, 82, 82, .15);
            color: var(--red);
        }

        .badge-active {
            background: rgba(61, 214, 140, .15);
            color: var(--green);
        }

        .badge-inactive {
            background: rgba(240, 82, 82, .15);
            color: var(--red);
        }

        .badge-low {
            background: rgba(245, 166, 35, .15);
            color: var(--amber);
        }

        .badge-out {
            background: rgba(240, 82, 82, .15);
            color: var(--red);
        }

        /* ── Alerts / Toasts ──────────────────────────────────────────────── */
        .alert {
            padding: 11px 16px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success {
            background: var(--green-dim);
            color: var(--green);
            border: 1px solid rgba(61, 214, 140, .25);
        }

        .alert-error {
            background: var(--red-dim);
            color: var(--red);
            border: 1px solid rgba(240, 82, 82, .25);
        }

        .alert-warning {
            background: var(--amber-dim);
            color: var(--amber);
            border: 1px solid rgba(245, 166, 35, .25);
        }

        .alert-info {
            background: var(--accent-glow);
            color: var(--accent);
            border: 1px solid rgba(108, 142, 245, .25);
        }

        /* ── Layout ───────────────────────────────────────────────────────── */
        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ──────────────────────────────────────────────────────── */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 100;
            transition: width .25s cubic-bezier(.4, 0, .2, 1);
        }

        .sidebar-logo {
            padding: 18px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: var(--topbar-h);
        }

        .sidebar-logo-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            flex-shrink: 0;
            background: linear-gradient(135deg, var(--accent), var(--accent-dim));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #fff;
        }

        .sidebar-logo-text .brand {
            font-family: var(--font-display);
            font-size: 17px;
            line-height: 1.1;
        }

        .sidebar-logo-text .sub {
            font-size: 10px;
            color: var(--slate);
            margin-top: 1px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 12px 8px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 10px;
            border-radius: 8px;
            color: var(--text-mid);
            font-size: 13px;
            font-weight: 400;
            border-left: 2px solid transparent;
            transition: all .15s;
            text-decoration: none;
            white-space: nowrap;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, .04);
            color: var(--text);
        }

        .nav-item.active {
            background: var(--accent-glow);
            color: var(--accent);
            border-left-color: var(--accent);
            font-weight: 500;
        }

        .nav-icon {
            font-size: 16px;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        .sidebar-footer {
            padding: 12px 8px;
            border-top: 1px solid var(--border);
        }

        /* ── Top Bar ──────────────────────────────────────────────────────── */
        .topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-w);
            right: 0;
            height: var(--topbar-h);
            z-index: 90;
            background: rgba(13, 15, 20, .9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            transition: left .25s;
        }

        .topbar-left h1 {
            font-family: var(--font-display);
            font-size: 22px;
            line-height: 1;
        }

        .topbar-left p {
            font-size: 11px;
            color: var(--slate);
            margin-top: 3px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            flex-shrink: 0;
            background: linear-gradient(135deg, rgba(108, 142, 245, .5), var(--accent-dim));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
        }

        /* ── Main Content ─────────────────────────────────────────────────── */
        .main {
            margin-left: var(--sidebar-w);
            padding-top: var(--topbar-h);
            min-height: 100vh;
        }

        .page {
            padding: 28px;
        }

        /* ── Stat Cards ───────────────────────────────────────────────────── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }

        .stat-card {
            position: relative;
            overflow: hidden;
            padding: 20px 22px;
        }

        .stat-card .stat-icon {
            position: absolute;
            right: -8px;
            top: -8px;
            font-size: 54px;
            opacity: .05;
            pointer-events: none;
        }

        .stat-card .stat-label {
            font-size: 10px;
            color: var(--slate);
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 10px;
        }

        .stat-card .stat-value {
            font-family: var(--font-display);
            font-size: 30px;
            line-height: 1;
            margin-bottom: 6px;
        }

        .stat-card .stat-sub {
            font-size: 11px;
            color: var(--text-mid);
        }

        /* ── Mini Bar Chart ───────────────────────────────────────────────── */
        .mini-chart {
            display: flex;
            gap: 3px;
            align-items: flex-end;
            height: 44px;
            margin: 14px 0 6px;
        }

        .mini-bar {
            flex: 1;
            border-radius: 3px 3px 0 0;
            min-height: 3px;
            transition: height .3s;
        }

        .chart-labels {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: var(--slate);
        }

        /* ── Progress Bar ─────────────────────────────────────────────────── */
        .progress-wrap {
            height: 4px;
            background: var(--border);
            border-radius: 2px;
        }

        .progress-bar {
            height: 100%;
            border-radius: 2px;
            transition: width .6s ease;
        }

        /* ── Stock Bar ────────────────────────────────────────────────────── */
        .stock-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stock-bar {
            width: 60px;
            height: 4px;
            background: var(--border);
            border-radius: 2px;
            flex-shrink: 0;
        }

        .stock-fill {
            height: 100%;
            border-radius: 2px;
        }

        /* ── Filters Bar ──────────────────────────────────────────────────── */
        .filters-bar {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-wrap {
            position: relative;
            flex: 1;
            min-width: 200px;
        }

        .search-wrap .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--slate);
            pointer-events: none;
            font-size: 13px;
        }

        .search-wrap input {
            padding-left: 30px;
        }

        .filter-tabs {
            display: flex;
            gap: 4px;
        }

        .filter-tab {
            padding: 7px 12px;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 500;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text-mid);
            cursor: pointer;
            transition: all .15s;
            font-family: var(--font-body);
        }

        .filter-tab:hover {
            color: var(--text);
            border-color: var(--border-hi);
        }

        .filter-tab.active {
            background: var(--accent-glow);
            color: var(--accent);
            border-color: var(--accent);
        }

        /* ── Pagination ───────────────────────────────────────────────────── */
        .pagination-wrap {
            padding: 12px 16px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            color: var(--slate);
        }

        .pagination {
            display: flex;
            gap: 4px;
        }

        .page-btn {
            padding: 4px 9px;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 500;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text-mid);
            cursor: pointer;
            transition: all .15s;
            font-family: var(--font-body);
        }

        .page-btn:hover {
            color: var(--text);
            border-color: var(--border-hi);
        }

        .page-btn.active {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }

        .page-btn:disabled {
            opacity: .4;
            cursor: not-allowed;
        }

        /* ── Modal ────────────────────────────────────────────────────────── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .75);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s;
        }

        .modal-overlay.open {
            opacity: 1;
            pointer-events: all;
        }

        .modal {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            width: 100%;
            max-width: 540px;
            max-height: 88vh;
            overflow-y: auto;
            transform: translateY(10px);
            transition: transform .2s;
        }

        .modal-overlay.open .modal {
            transform: translateY(0);
        }

        .modal-header {
            padding: 20px 22px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .modal-close {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text-mid);
            width: 28px;
            height: 28px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all .15s;
        }

        .modal-close:hover {
            border-color: var(--border-hi);
            color: var(--text);
        }

        /* ── Order Items Adder ────────────────────────────────────────────── */
        .order-item-row {
            background: var(--surface);
            border-radius: 10px;
            padding: 14px 16px;
            border: 1px solid var(--border);
            transition: border-color .15s;
        }

        .order-item-row.has-error {
            border-color: rgba(240, 82, 82, .5);
        }

        .item-grid {
            display: grid;
            grid-template-columns: 1fr 90px;
            gap: 10px;
            margin-top: 10px;
        }

        .item-preview {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: var(--text-mid);
            padding: 8px 10px;
            background: var(--card);
            border-radius: 6px;
        }

        .add-item-btn {
            border: 1.5px dashed var(--border-hi);
            background: transparent;
            border-radius: 10px;
            padding: 12px;
            color: var(--text-mid);
            font-size: 13px;
            cursor: pointer;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-family: var(--font-body);
            transition: all .15s;
        }

        .add-item-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        /* ── Order Summary Box ────────────────────────────────────────────── */
        .order-summary {
            position: sticky;
            top: calc(var(--topbar-h) + 14px);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 8px;
        }

        .summary-row span:first-child {
            color: var(--slate);
        }

        .summary-divider {
            height: 1px;
            background: var(--border);
            margin: 10px 0;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .summary-total-label {
            font-size: 13px;
            font-weight: 500;
        }

        .summary-total-value {
            font-family: var(--font-display);
            font-size: 22px;
            color: var(--accent);
        }

        /* ── Two-col create layout ────────────────────────────────────────── */
        .create-grid {
            display: grid;
            grid-template-columns: 1fr 270px;
            gap: 20px;
            align-items: start;
            max-width: 820px;
        }

        /* ── Utilities ────────────────────────────────────────────────────── */
        .mono {
            font-family: var(--font-mono);
        }

        .muted {
            color: var(--slate);
        }

        .mid {
            color: var(--text-mid);
        }

        .accent {
            color: var(--accent);
        }

        .green {
            color: var(--green);
        }

        .red {
            color: var(--red);
        }

        .amber {
            color: var(--amber);
        }

        .fw-500 {
            font-weight: 500;
        }

        .text-sm {
            font-size: 12px;
        }

        .text-xs {
            font-size: 11px;
        }

        .mb-4 {
            margin-bottom: 4px;
        }

        .mb-8 {
            margin-bottom: 8px;
        }

        .mb-12 {
            margin-bottom: 12px;
        }

        .mb-16 {
            margin-bottom: 16px;
        }

        .mb-20 {
            margin-bottom: 20px;
        }

        .mb-24 {
            margin-bottom: 24px;
        }

        .mt-4 {
            margin-top: 4px;
        }

        .mt-8 {
            margin-top: 8px;
        }

        .mt-16 {
            margin-top: 16px;
        }

        .flex {
            display: flex;
        }

        .flex-center {
            display: flex;
            align-items: center;
        }

        .gap-6 {
            gap: 6px;
        }

        .gap-8 {
            gap: 8px;
        }

        .gap-10 {
            gap: 10px;
        }

        .gap-12 {
            gap: 12px;
        }

        .justify-between {
            justify-content: space-between;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .error-msg {
            font-size: 11px;
            color: var(--red);
            margin-top: 4px;
        }

        .success-screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 55vh;
            text-align: center;
        }

        /* ── Animations ───────────────────────────────────────────────────── */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        @keyframes slideIn {
            from {
                transform: translateX(-10px);
                opacity: 0
            }

            to {
                transform: translateX(0);
                opacity: 1
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg)
            }
        }

        .fade-in {
            animation: fadeIn .3s ease forwards;
        }

        .slide-in {
            animation: slideIn .25s ease forwards;
        }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid var(--border);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin .7s linear infinite;
            display: inline-block;
        }

        /* ── Responsive ───────────────────────────────────────────────────── */
        @media (max-width: 900px) {
            :root {
                --sidebar-w: 64px;
            }

            .sidebar-logo-text,
            .nav-label {
                display: none;
            }

            .sidebar-footer-text {
                display: none;
            }

            .stats-grid,
            .grid-3 {
                grid-template-columns: repeat(2, 1fr);
            }

            .create-grid {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
            }
        }

        @media (max-width: 600px) {

            .stats-grid,
            .grid-4 {
                grid-template-columns: 1fr 1fr;
            }

            .topbar {
                padding: 0 16px;
            }

            .page {
                padding: 16px;
            }
        }
    </style>

    @stack('styles')
</head>

<body>
    <div class="layout">

        {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <div class="sidebar-logo-icon">⬡</div>
                <div class="sidebar-logo-text">
                    <div class="brand">StockFlow</div>
                    <div class="sub">Order Management</div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="{{ route('dashboard') }}"
                    class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="nav-icon">⬡</span>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="{{ route('orders.index') }}"
                    class="nav-item {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                    <span class="nav-icon">◈</span>
                    <span class="nav-label">Orders</span>
                </a>
                <a href="{{ route('products.index') }}"
                    class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
                    <span class="nav-icon">◇</span>
                    <span class="nav-label">Products</span>
                </a>
                <a href="{{ route('orders.create') }}"
                    class="nav-item {{ request()->routeIs('orders.create') ? 'active' : '' }}">
                    <span class="nav-icon">⊕</span>
                    <span class="nav-label">New Order</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nav-item"
                        style="width:100%;border:none;cursor:pointer;background:transparent;text-align:left;">
                        <span class="nav-icon">⇥</span>
                        <span class="nav-label sidebar-footer-text">Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- ── Top Bar ───────────────────────────────────────────────────── --}}
        <header class="topbar">
            <div class="topbar-left">
                <h1>@yield('page-title', 'Dashboard')</h1>
                <p>@yield('page-subtitle', '')</p>
            </div>
            <div class="topbar-right">
                @yield('topbar-actions')
                <div class="avatar">{{ substr(auth()->user()->name ?? 'U', 0, 1) }}</div>
            </div>
        </header>

        {{-- ── Main ─────────────────────────────────────────────────────── --}}
        <main class="main">
            <div class="page fade-in">

                {{-- Flash messages --}}
                @if(session('success'))
                <div class="alert alert-success">✓ {{ session('success') }}</div>
                @endif
                @if(session('error'))
                <div class="alert alert-error">✕ {{ session('error') }}</div>
                @endif
                @if($errors->any())
                <div class="alert alert-error">
                    ✕ {{ $errors->first() }}
                </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>

</html>