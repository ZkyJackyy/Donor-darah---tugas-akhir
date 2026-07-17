@extends('layouts.admin')

@section('page_title', 'Overview Dashboard')

@section('content')
<div class="space-y-8">
    <!-- Header/Greeting Area -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Selamat datang kembali, {{ auth()->user()->name ?? 'Admin' }}! 👋</h2>
            <p class="text-sm text-gray-500 mt-1">Berikut adalah ringkasan aktivitas donor darah dan permintaan rumah sakit hari ini.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.blood-requests.create') }}" class="inline-flex items-center justify-center bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition duration-200 shadow-lg shadow-brand-500/30">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Buat Permintaan
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Stat 1 -->
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-6 relative overflow-hidden group hover:shadow-soft transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-red-50 rounded-xl text-brand-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
            </div>
            <div>
                <h3 class="text-3xl font-extrabold text-gray-900">{{ $activeRequestsCount ?? '0' }}</h3>
                <p class="text-sm font-medium text-gray-500 mt-1">Permintaan Aktif</p>
            </div>
        </div>

        <!-- Stat 2 -->
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-6 relative overflow-hidden group hover:shadow-soft transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-blue-50 rounded-xl text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>
            <div>
                <h3 class="text-3xl font-extrabold text-gray-900">{{ $totalDonors ?? '0' }}</h3>
                <p class="text-sm font-medium text-gray-500 mt-1">Total Pendonor Aktif</p>
            </div>
        </div>

        <!-- Stat 3 -->
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-6 relative overflow-hidden group hover:shadow-soft transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                </div>
            </div>
            <div>
                <h3 class="text-3xl font-extrabold text-gray-900">{{ $totalDonationsCount ?? '0' }}</h3>
                <p class="text-sm font-medium text-gray-500 mt-1">Total Donasi Selesai</p>
            </div>
        </div>

        <!-- Stat 4 -->
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-6 relative overflow-hidden group hover:shadow-soft transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-purple-50 rounded-xl text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
            </div>
            <div>
                <h3 class="text-3xl font-extrabold text-gray-900">{{ $totalHospitals ?? '0' }}</h3>
                <p class="text-sm font-medium text-gray-500 mt-1">Rumah Sakit Rekanan</p>
            </div>
        </div>
    </div>

    <!-- Charts & Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Chart -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-card border border-gray-100 p-6 relative">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="font-bold text-gray-900 text-lg">Tren Aktivitas Donor & Permintaan</h3>
                    <p class="text-xs text-gray-500 mt-1">Tren permintaan darah 6 bulan terakhir</p>
                </div>
            </div>
            <div class="h-[300px] w-full relative">
                <canvas id="trendsChart"></canvas>
            </div>
        </div>

        <!-- Recent Activity Sidebar -->
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-6 flex flex-col h-[400px]">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-gray-900 text-lg">Aktivitas Terbaru</h3>
            </div>
            
            <div class="flex-1 overflow-y-auto pr-2 relative space-y-6">
                <!-- Line timeline -->
                <div class="absolute left-[11px] top-2 bottom-2 w-0.5 bg-gray-100 z-0"></div>
                
                @forelse($recentRequests ?? [] as $request)
                <div class="relative z-10 flex gap-4 items-start group">
                    <div class="w-6 h-6 mt-0.5 rounded-full bg-white border-2 border-brand-500 flex-shrink-0 flex items-center justify-center shadow-sm">
                        <div class="w-2 h-2 rounded-full bg-brand-500 group-hover:scale-150 transition-transform"></div>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-800 leading-tight">{{ $request->hospital_name }}</p>
                        <p class="text-xs text-gray-500 mt-1">Butuh <span class="font-extrabold text-brand-600">{{ $request->blood_type }}{{ $request->rhesus }}</span> ({{ $request->required_bags }} Kantong)</p>
                        <p class="text-[10px] font-semibold text-gray-400 mt-1">{{ $request->created_at->diffForHumans() }}</p>
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
                <a href="{{ route('admin.blood-requests.index') }}" class="text-xs font-bold text-brand-600 hover:text-brand-700 transition-colors">Lihat Semua Permintaan &rarr;</a>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
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
