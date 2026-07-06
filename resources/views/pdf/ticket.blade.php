<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Event Ticket</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111; margin: 40px; }
        h1 { margin-bottom: 4px; }
        .muted { color: #555; font-size: 14px; }
        .section { margin-top: 24px; }
        .qr { margin-top: 24px; text-align: center; }
        .qr img { width: 200px; height: 200px; }
    </style>
</head>
<body>
    <h1>{{ $event->name }}</h1>
    <p class="muted">{{ $event->venue }}</p>

    <div class="section">
        <strong>{{ $registration->attendee_first_name }} {{ $registration->attendee_last_name }}</strong><br>
        <span class="muted">{{ $registration->attendee_email }}</span>
    </div>

    <div class="section">
        <div>Ticket: {{ $ticketType->name }}</div>
        <div>Registration: {{ $registration->registration_number }}</div>
        @if(!empty($tableName))
            <div>Table: {{ $tableName }}</div>
        @endif
    </div>

    <div class="qr">
        <img src="{{ $qrDataUri }}" alt="QR Code">
        <p class="muted">Present this code at check-in</p>
    </div>
</body>
</html>
