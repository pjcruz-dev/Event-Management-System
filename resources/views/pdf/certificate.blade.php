<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Certificate</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; text-align: center; margin: 60px; }
        h1 { font-size: 32px; margin-bottom: 12px; }
        h2 { font-size: 22px; margin: 24px 0; }
        p { font-size: 16px; color: #444; }
    </style>
</head>
<body>
    <h1>{{ $headline }}</h1>
    <p>This certifies that</p>
    <h2>{{ $registration->attendee_first_name }} {{ $registration->attendee_last_name }}</h2>
    <p>attended</p>
    <h2>{{ $event->name }}</h2>
    <p>{{ $registration->registration_number }}</p>
</body>
</html>
