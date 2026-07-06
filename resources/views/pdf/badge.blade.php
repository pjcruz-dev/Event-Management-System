<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Event Badge</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 24px; text-align: center; }
        .banner { background: {{ $primaryColor }}; color: #fff; padding: 12px; font-size: 18px; font-weight: bold; }
        .name { font-size: 28px; font-weight: bold; margin: 24px 0 8px; }
        .meta { font-size: 14px; color: #444; }
        .qr img { width: 140px; height: 140px; margin-top: 16px; }
    </style>
</head>
<body>
    <div class="banner">{{ $event->name }}</div>
    <div class="name">{{ $registration->attendee_first_name }} {{ $registration->attendee_last_name }}</div>
    @if($company)
        <div class="meta">{{ $company }}</div>
    @endif
    @if($jobTitle)
        <div class="meta">{{ $jobTitle }}</div>
    @endif
    <div class="qr">
        <img src="{{ $qrDataUri }}" alt="QR Code">
    </div>
</body>
</html>
