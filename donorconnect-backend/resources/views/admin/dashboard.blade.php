@extends('layouts.admin')

@section('page_title', 'Dashboard')

@section('content')
<div class="space-y-8">
    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex items-center">
            <div class="p-3 bg-red-100 rounded-lg text-red-600 mr-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Permintaan Aktif</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $activeRequestsCount }}</h3>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex items-center">
            <div class="p-3 bg-blue-100 rounded-lg text-blue-600 mr-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Pendonor</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $totalDonors }}</h3>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex items-center">
            <div class="p-3 bg-green-100 rounded-lg text-green-600 mr-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Donasi</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $totalDonationsCount }}</h3>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex items-center">
            <div class="p-3 bg-yellow-100 rounded-lg text-yellow-600 mr-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Permintaan Selesai</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $totalCompletedRequests }}</h3>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Chart Section -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-semibold text-gray-800 uppercase tracking-wider text-sm">Tren Permintaan Darah (7 Hari Terakhir)</h3>
                <span class="text-xs text-gray-400 font-medium italic">Data diperbarui secara real-time</span>
            </div>
            <div class="h-80">
                <canvas id="trendsChart"></canvas>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 overflow-hidden">
            <h3 class="font-semibold text-gray-800 uppercase tracking-wider text-sm mb-6">Aktivitas Terbaru</h3>
            <div class="space-y-6">
                @forelse($recentRequests as $request)
                <div class="flex items-start space-x-4 border-l-2 border-red-500 pl-4 py-1">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-800 truncate">{{ $request->hospital_name }}</p>
                        <p class="text-xs text-gray-500 mt-1">Butuh: <span class="font-bold text-red-600">{{ $request->blood_type }}{{ $request->rhesus }}</span> • {{ $request->required_bags }} Kantong</p>
                        <p class="text-[10px] text-gray-400 mt-1 uppercase">{{ $request->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-[10px] font-bold uppercase tracking-wider">
                        {{ $request->status }}
                    </span>
                </div>
                @empty
                <div class="py-10 text-center opacity-50">
                    <p class="text-sm font-medium text-gray-400">Belum ada aktivitas</p>
                </div>
                @endforelse
            </div>
            @if($recentRequests->isNotEmpty())
            <div class="mt-8 pt-6 border-t border-gray-50 text-center">
                <a href="{{ route('admin.blood-requests.index') }}" class="text-xs font-bold text-red-600 hover:text-red-700 uppercase tracking-widest">Lihat Semua Permintaan</a>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    const ctx = document.getElementById('trendsChart').getContext('2d');
    
    // Gradient for chart
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(229, 62, 62, 0.2)');
    gradient.addColorStop(1, 'rgba(229, 62, 62, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($trends->pluck('date')) !!},
            datasets: [{
                label: 'Jumlah Permintaan',
                data: {!! json_encode($trends->pluck('count')) !!},
                borderColor: '#E53E3E',
                borderWidth: 3,
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#E53E3E',
                pointBorderColor: '#FFFFFF',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [5, 5], color: '#E2E8F0' },
                    ticks: { font: { size: 10, family: 'Inter' }, stepSize: 1 }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10, family: 'Inter' } }
                }
            }
        }
    });
</script>
@endpush
@endsection
