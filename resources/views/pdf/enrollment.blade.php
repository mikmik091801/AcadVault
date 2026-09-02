<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate of Registration — {{ $student?->student_number }}</title>
    <style>
        @page { margin: 34px 40px; }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10.5px;
            color: #1f2937;
            line-height: 1.5;
        }

        /* ---------- Institution header ---------- */
        .header {
            border-bottom: 3px solid #22375a;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; border: 0; padding: 0; }
        .logo { width: 58px; }
        .inst-name {
            font-size: 19px;
            font-weight: bold;
            color: #22375a;
            letter-spacing: -0.3px;
        }
        .inst-sub {
            font-size: 8.5px;
            letter-spacing: 2.2px;
            text-transform: uppercase;
            color: #a87c26;
            font-weight: bold;
        }
        .doc-meta {
            text-align: right;
            font-size: 8.5px;
            color: #6b7280;
            line-height: 1.7;
        }
        .doc-title {
            font-size: 13px;
            font-weight: bold;
            color: #22375a;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ---------- Sections ---------- */
        .section-title {
            font-size: 8.5px;
            font-weight: bold;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 1px solid #e3e8ef;
            padding-bottom: 5px;
            margin: 20px 0 10px;
        }

        .info { width: 100%; border-collapse: collapse; }
        .info td { padding: 4px 0; border: 0; vertical-align: top; }
        .info .label {
            width: 108px;
            color: #6b7280;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info .value { font-weight: bold; color: #14243c; }

        /* ---------- Class list ---------- */
        .records {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .records th {
            background: #22375a;
            color: #fff;
            font-size: 8.5px;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-align: left;
            padding: 9px 10px;
        }
        .records td {
            padding: 10px;
            border-bottom: 1px solid #e8ecf3;
        }
        .records tr:nth-child(even) td { background: #fafbfd; }
        .seq { color: #9aa5b4; }
        .pending {
            font-size: 8px;
            color: #a87c26;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .total {
            margin-top: 8px;
            font-size: 9.5px;
            font-weight: bold;
            color: #14243c;
            text-align: right;
        }

        /* ---------- Verification block ---------- */
        .verify {
            margin-top: 26px;
            border: 1px solid #e3e8ef;
            border-radius: 6px;
            background: #f7f9fc;
            padding: 14px;
        }
        .verify-table { width: 100%; border-collapse: collapse; }
        .verify-table td { border: 0; vertical-align: middle; }
        .qr-cell { width: 132px; text-align: center; }
        .qr { width: 118px; height: 118px; }
        .verify-title {
            font-size: 11px;
            font-weight: bold;
            color: #22375a;
            margin-bottom: 4px;
        }
        .verify-text { font-size: 9px; color: #4b5563; }
        .hash {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 7.5px;
            color: #4b5563;
            word-wrap: break-word;
            background: #fff;
            border: 1px solid #e3e8ef;
            border-radius: 3px;
            padding: 5px 6px;
            margin-top: 6px;
        }
        .verify-url {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 8px;
            color: #a87c26;
            margin-top: 5px;
        }

        .footer {
            margin-top: 20px;
            border-top: 1px solid #e3e8ef;
            padding-top: 9px;
            font-size: 7.5px;
            color: #9aa5b4;
            text-align: center;
            line-height: 1.7;
        }
    </style>
</head>
<body>

    {{-- ============ Institution header ============ --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width:66px;">
                    @if ($logo)
                        <img src="{{ $logo }}" class="logo" alt="">
                    @endif
                </td>
                <td>
                    <div class="inst-name">AcadVault</div>
                    <div class="inst-sub">Secure &middot; Verified &middot; Trusted</div>
                    <div style="font-size:8.5px;color:#6b7280;margin-top:2px;">
                        Academic Records Management System
                    </div>
                </td>
                <td class="doc-meta">
                    <div class="doc-title">Certificate of Registration</div>
                    <div>Document ID: {{ $document->uuid }}</div>
                    <div>Issued: {{ $document->created_at?->format('d M Y, H:i') }}</div>
                    <div>Issued by: {{ $document->issuer?->name ?? 'System' }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ============ Student information ============ --}}
    <div class="section-title">Student Information</div>
    <table class="info">
        <tr>
            <td class="label">Name</td>
            <td class="value">{{ $student?->user?->name ?? '—' }}</td>
            <td class="label">Student No.</td>
            <td class="value">{{ $student?->student_number ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Program</td>
            <td class="value">{{ $student?->program ?? '—' }}</td>
            <td class="label">Year Level</td>
            <td class="value">{{ $student?->yearLevelLabel() ?? '—' }}</td>
        </tr>
    </table>

    {{-- ============ Enrolled classes ============ --}}
    <div class="section-title">Enrolled Classes</div>
    <table class="records">
        <thead>
            <tr>
                <th style="width:26px;">#</th>
                <th style="width:80px;">Code</th>
                <th>Course Title</th>
                <th style="width:140px;">Instructor</th>
                <th style="width:74px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($classes as $index => $enrollment)
                <tr>
                    <td class="seq">{{ $index + 1 }}</td>
                    <td><strong>{{ $enrollment->course?->code ?? '—' }}</strong></td>
                    <td>{{ $enrollment->course?->title ?? '—' }}</td>
                    <td>{{ $enrollment->course?->faculty?->name ?? 'Unassigned' }}</td>
                    <td>
                        @if ($enrollment->isDropPending())
                            {{-- Still enrolled: the registrar has not decided yet. --}}
                            <span class="pending">Drop pending</span>
                        @else
                            Enrolled
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;color:#9aa5b4;">
                        No enrolled classes.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="total">
        Total: {{ $classes->count() }} {{ \Illuminate\Support\Str::plural('class', $classes->count()) }}
    </div>

    {{-- ============ QR verification ============ --}}
    <div class="verify">
        <table class="verify-table">
            <tr>
                <td class="qr-cell">
                    <img src="{{ $qrDataUri }}" class="qr" alt="Verification QR code">
                </td>
                <td>
                    <div class="verify-title">Scan to verify this certificate</div>
                    <div class="verify-text">
                        This certificate carries a SHA-256 fingerprint of the enrollment
                        list taken at the moment of issue. Scanning the code re-computes
                        the fingerprint from the live record and reports whether this
                        certificate still reflects the student's current enrollment.
                    </div>
                    <div class="verify-url">{{ $verifyUrl }}</div>
                    <div class="hash">SHA-256: {{ $document->file_hash }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        This document was generated electronically by AcadVault and is valid without a signature.<br>
        Enrolling in or dropping a class after this date will supersede this certificate — verify at the address above before accepting it.
    </div>

</body>
</html>
