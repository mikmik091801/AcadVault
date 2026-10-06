@props(['url'])

@php
    // Rendered as an inline PNG data URI so the QR still appears even after
    // Render's ephemeral filesystem wipes the stored copy on every deploy.
    $dataUri = app(\App\Support\QrCodeGenerator::class)->dataUri($url, 220);
@endphp

<img src="{{ $dataUri }}"
     alt="QR code linking to the public verification page"
     class="img-fluid mb-3" style="max-width:210px;">
