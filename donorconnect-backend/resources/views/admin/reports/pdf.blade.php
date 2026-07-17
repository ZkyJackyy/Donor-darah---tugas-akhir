<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 14px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #e53e3e; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 24px; font-weight: bold; color: #e53e3e; margin: 0;}
        .subtitle { font-size: 14px; color: #666; margin-top: 5px;}
        .stats { display: table; width: 100%; margin-bottom: 20px; }
        .stat-box { display: table-cell; width: 25%; text-align: center; padding: 10px; border: 1px solid #eee; }
        .stat-value { font-size: 20px; font-weight: bold; color: #e53e3e; }
        .stat-label { font-size: 11px; color: #666; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f7fafc; color: #4a5568; font-weight: bold; }
        h2 { font-size: 16px; color: #4a5568; margin-top: 25px; }
    </style>
</head>
<body>

    <div class="header">
        <h1 class="title">Sahabat Donor - Laporan Bulanan</h1>
        <p class="subtitle">{{ \Carbon\Carbon::create()->month($month)->format('F') }} {{ $year }} | UDD PMI Kota Padang</p>
        <p class="subtitle" style="font-size: 12px">Generated: {{ \Carbon\Carbon::now()->toDateTimeString() }}</p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="stat-value">{{ $totalSuccessfulDonors }}</div>
            <div class="stat-label">Donasi Berhasil</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $totalRequests }}</div>
            <div class="stat-label">Total Permintaan</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $totalBagsRequested }}</div>
            <div class="stat-label">Kantong Darah</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $completedRequests }}</div>
            <div class="stat-label">Req. Selesai</div>
        </div>
    </div>

    <h2>Donasi per Golongan Darah</h2>
    <table>
        <thead>
            <tr><th>Golongan Darah</th><th>Jumlah</th></tr>
        </thead>
        <tbody>
            @forelse($bloodTypeBreakdown as $row)
            <tr><td>{{ $row->blood_type }}</td><td>{{ $row->count }}</td></tr>
            @empty
            <tr><td colspan="2">Belum ada data bulan ini</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Permintaan per Tingkat Urgensi</h2>
    <table>
        <thead>
            <tr><th>Urgensi</th><th>Jumlah</th></tr>
        </thead>
        <tbody>
            @forelse($urgencyBreakdown as $row)
            <tr><td>{{ strtoupper($row->urgency_level) }}</td><td>{{ $row->count }}</td></tr>
            @empty
            <tr><td colspan="2">Belum ada data bulan ini</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Riwayat Verifikasi Donasi</h2>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Pendonor</th>
                <th>Golongan</th>
                <th>Lokasi / RS</th>
                <th>Ref. Req</th>
            </tr>
        </thead>
        <tbody>
            @forelse($histories as $history)
            <tr>
                <td>{{ \Carbon\Carbon::parse($history->donor_date)->format('d/m/Y') }}</td>
                <td>{{ $history->user->name }}</td>
                <td>{{ $history->user->blood_type }}{{ $history->user->rhesus }}</td>
                <td>{{ $history->location_name }}</td>
                <td>#{{ $history->blood_request_id }}</td>
            </tr>
            @empty
            <tr><td colspan="5">Belum ada riwayat donor bulan ini</td></tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
