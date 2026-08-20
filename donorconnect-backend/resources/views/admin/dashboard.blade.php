@extends('layouts.admin')

@section('page_title', 'Overview Dashboard')

@section('content')
<div class="space-y-8" x-data='dashboardWatcher({
        active_requests: {{ $activeRequestsCount ?? 0 }},
        total_donors: {{ $totalDonors ?? 0 }},
        total_donations: {{ $totalDonationsCount ?? 0 }},
        total_hospitals: {{ $totalHospitals ?? 0 }},
        latest_request_id: {{ optional($recentRequests->first())->id ?? 0 }}
    })'>
    <!-- Header/Greeting Area -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 bg-white p-6 rounded-lg border border-gray-200">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Selamat datang kembali, {{ auth()->user()->name ?? 'Admin' }}</h2>
            <p class="text-sm text-gray-500 mt-1">Berikut adalah ringkasan aktivitas donor darah dan permintaan rumah sakit hari ini.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.blood-requests.create') }}" class="inline-flex items-center justify-center bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-md text-sm font-semibold transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Buat Permintaan
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Stat 1 -->
        <div class="bg-white rounded-lg border border-gray-200 border-l-2 {{ ($activeRequestsCount ?? 0) > 0 ? 'border-l-brand-600' : 'border-l-gray-200' }} p-5">
            @if(($activeRequestsCount ?? 0) > 0)
                <h3 class="text-3xl font-semibold text-gray-900 font-mono">{{ $activeRequestsCount }}</h3>
            @else
                <h3 class="text-3xl font-semibold text-gray-300 font-mono">&mdash;</h3>
            @endif
            <p class="text-sm font-medium text-gray-500 mt-1">Permintaan Aktif</p>
            @if(($activeRequestsCount ?? 0) === 0)
                <p class="text-xs text-gray-400 mt-0.5">Tidak ada permintaan aktif saat ini</p>
            @endif
        </div>

        <!-- Stat 2 -->
        <div class="bg-white rounded-lg border border-gray-200 border-l-2 border-l-sky-600 p-5">
            <h3 class="text-3xl font-semibold text-gray-900 font-mono">{{ $totalDonors ?? '0' }}</h3>
            <p class="text-sm font-medium text-gray-500 mt-1">Total Pendonor Aktif</p>
        </div>

        <!-- Stat 3 -->
        <div class="bg-white rounded-lg border border-gray-200 border-l-2 border-l-emerald-600 p-5">
            <h3 class="text-3xl font-semibold text-gray-900 font-mono">{{ $totalDonationsCount ?? '0' }}</h3>
            <p class="text-sm font-medium text-gray-500 mt-1">Total Donasi Selesai</p>
        </div>

        <!-- Stat 4 -->
        <div class="bg-white rounded-lg border border-gray-200 border-l-2 border-l-violet-600 p-5">
            <h3 class="text-3xl font-semibold text-gray-900 font-mono">{{ $totalHospitals ?? '0' }}</h3>
            <p class="text-sm font-medium text-gray-500 mt-1">Rumah Sakit Rekanan</p>
        </div>
    </div>

    <!-- Charts & Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Chart -->
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 p-6 relative">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="font-semibold text-gray-900 text-lg">Tren Aktivitas Donor & Permintaan</h3>
                    <p class="text-xs text-gray-500 mt-1">Tren permintaan darah 6 bulan terakhir</p>
                </div>
            </div>
            <div class="h-[300px] w-full relative">
                <canvas id="trendsChart"></canvas>
            </div>
        </div>

        <!-- Recent Activity Sidebar -->
        <div class="bg-white rounded-lg border border-gray-200 p-6 flex flex-col h-[400px]">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-semibold text-gray-900 text-lg">Aktivitas Terbaru</h3>
            </div>

            <div class="flex-1 overflow-y-auto pr-2 relative space-y-6">
                <!-- Line timeline -->
                <div class="absolute left-[11px] top-2 bottom-2 w-0.5 bg-gray-100 z-0"></div>

                @forelse($recentRequests ?? [] as $request)
                <div class="relative z-10 flex gap-4 items-start">
                    <div class="w-6 h-6 mt-0.5 rounded-full bg-white border-2 border-brand-500 flex-shrink-0 flex items-center justify-center">
                        <div class="w-2 h-2 rounded-full bg-brand-500"></div>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800 leading-tight">{{ $request->hospital_name }}</p>
                        <p class="text-xs text-gray-500 mt-1">Butuh <span class="font-semibold text-brand-600">{{ $request->blood_type }}{{ $request->rhesus }}</span> ({{ $request->required_bags }} Kantong)</p>
                        <p class="text-[10px] font-medium text-gray-400 mt-1 font-mono">{{ $request->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @empty
                <div class="relative z-10 flex flex-col items-center justify-center h-full text-center opacity-50">
                    <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-sm font-medium text-gray-500">Belum ada aktivitas</p>
                </div>
                @endforelse
            </div>

            @if(isset($recentRequests) && $recentRequests->isNotEmpty())
            <div class="mt-4 pt-4 border-t border-gray-100 text-center">
                <a href="{{ route('admin.blood-requests.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 transition-colors">Lihat Semua Permintaan &rarr;</a>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('dashboardWatcher', (initialStats) => ({
        init() {
            setInterval(async () => {
                try {
                    const res = await fetch('/api/admin-poll/dashboard');
                    if (!res.ok) return;
                    const current = await res.json();
                    const changed = Object.keys(initialStats).some(key => current[key] !== initialStats[key]);
                    if (changed) window.location.reload();
                } catch (e) {}
            }, 15000);
        }
    }));
});

    document.addEventListener('DOMContentLoaded', function() {
        if(!document.getElementById('trendsChart')) return;
        
        const ctx = document.getElementById('trendsChart').getContext('2d');
        
        // Gradient for chart
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(239, 68, 68, 0.15)'); // brand-500 with opacity
        gradient.addColorStop(1, 'rgba(239, 68, 68, 0)');
        
        const labels = {!! json_encode($trends->pluck('month')) !!};
        const data = {!! json_encode($trends->pluck('count')) !!};

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Permintaan Darah',
                    data: data,
                    borderColor: '#ef4444',
                    borderWidth: 3,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4, // Smooth curves
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#ef4444',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#ef4444',
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        titleFont: { family: 'Plus Jakarta Sans', size: 13, weight: 'bold' },
                        bodyFont: { family: 'Plus Jakarta Sans', size: 12 },
                        cornerRadius: 8,
                        displayColors: false,
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { borderDash: [4, 4], color: '#f1f5f9', drawBorder: false },
                        ticks: { font: { size: 11, family: 'Plus Jakarta Sans' }, stepSize: 5, color: '#94a3b8', padding: 10 }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { size: 11, family: 'Plus Jakarta Sans' }, color: '#94a3b8', padding: 10 }
                    }
                }
            }
        });
    });
</script>
@endpush
@endsection
