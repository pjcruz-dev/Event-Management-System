<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $event->name }} — Summary Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-top: 20px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f5f5f5; }
        .muted { color: #666; font-size: 11px; }
        .grid { display: table; width: 100%; margin-top: 12px; }
        .grid-row { display: table-row; }
        .grid-cell { display: table-cell; width: 50%; padding: 8px; vertical-align: top; }
        .metric { font-size: 18px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ $event->name }}</h1>
    <p class="muted">Generated {{ now()->toDayDateTimeString() }}</p>

    <div class="grid">
        <div class="grid-row">
            <div class="grid-cell">
                <div class="muted">Total revenue</div>
                <div class="metric">{{ $metrics['summary']['currency'] }} {{ number_format($metrics['summary']['total_revenue'], 2) }}</div>
            </div>
            <div class="grid-cell">
                <div class="muted">Confirmed registrations</div>
                <div class="metric">{{ $metrics['summary']['confirmed_registrations'] }}</div>
            </div>
        </div>
        <div class="grid-row">
            <div class="grid-cell">
                <div class="muted">Check-in rate</div>
                <div class="metric">{{ number_format($metrics['summary']['check_in_rate'] * 100, 1) }}%</div>
            </div>
            <div class="grid-cell">
                <div class="muted">Checked in</div>
                <div class="metric">{{ $metrics['summary']['checked_in_count'] }}</div>
            </div>
        </div>
    </div>

    <h2>Top ticket types</h2>
    <table>
        <thead>
            <tr>
                <th>Ticket type</th>
                <th>Volume</th>
                <th>Revenue</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($metrics['top_ticket_types'] as $ticket)
                <tr>
                    <td>{{ $ticket['name'] }}</td>
                    <td>{{ $ticket['volume'] }}</td>
                    <td>{{ number_format($ticket['revenue'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No ticket sales yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Geographic breakdown</h2>
    <table>
        <thead>
            <tr>
                <th>Country</th>
                <th>City</th>
                <th>Registrations</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($metrics['geographic_breakdown'] as $geo)
                <tr>
                    <td>{{ $geo['country'] }}</td>
                    <td>{{ $geo['city'] }}</td>
                    <td>{{ $geo['count'] }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No geographic data collected.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
