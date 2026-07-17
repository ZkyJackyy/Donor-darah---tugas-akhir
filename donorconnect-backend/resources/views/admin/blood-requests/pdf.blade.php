<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 14px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #e53e3e; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 24px; font-weight: bold; color: #e53e3e; margin: 0;}
        .subtitle { font-size: 14px; color: #666; margin-top: 5px;}
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f7fafc; color: #4a5568; font-weight: bold; }
        .badge { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase;}
        .verified { background: #c6f6d5; color: #22543d; }
        .confirmed { background: #bee3f8; color: #2b6cb0; }
        .notified { background: #fefcbf; color: #744210; }
        .declined { background: #fed7d7; color: #822727; }
    </style>
</head>
<body>

    <div class="header">
        <h1 class="title">Sahabat Donor Official Report</h1>
        <p class="subtitle">Request #{{ $bloodRequest->id }} | {{ $bloodRequest->hospital_name }}</p>
        <p class="subtitle" style="font-size: 12px">Generated: {{ \Carbon\Carbon::now()->toDateTimeString() }}</p>
    </div>

    <div style="margin-bottom: 20px;">
        <strong>Blood Required:</strong> {{ $bloodRequest->required_bags }} Bags ({{ $bloodRequest->blood_type }}{{ $bloodRequest->rhesus }})<br>
        <strong>Urgency:</strong> {{ strtoupper($bloodRequest->urgency_level) }}<br>
        <strong>Deadline:</strong> {{ $bloodRequest->deadline ? $bloodRequest->deadline->format('Y-m-d H:i') . ' WIB' : '-' }}<br>
        <strong>Total Candidates Recruited:</strong> {{ $bloodRequest->donorCandidates->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Donor Name</th>
                <th>Phone Number</th>
                <th>Distance</th>
                <th>Engagement Status</th>
                <th>Screening</th>
                <th>Verified At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bloodRequest->donorCandidates as $index => $candidate)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $candidate->user->name }}</td>
                <td>{{ $candidate->user->phone }}</td>
                <td>{{ number_format($candidate->distance_km, 2) }} km</td>
                <td>
                    <span class="badge {{ $candidate->status }}">{{ $candidate->status }}</span>
                </td>
                <td>
                    @if($candidate->screening)
                        {{ ($candidate->screening->health_status && $candidate->screening->min_weight && $candidate->screening->no_medicine && $candidate->screening->not_pregnant) ? 'Lolos' : 'Tidak Lolos' }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ $candidate->verified_at ? $candidate->verified_at->format('Y-m-d H:i') : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
