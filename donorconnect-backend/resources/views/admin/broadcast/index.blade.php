@extends('layouts.admin')

@section('page_title', 'Broadcast WhatsApp')

@section('content')
<div class="space-y-6">

    <div class="animate-fade-in-up">
        <h1 class="font-bold text-2xl text-slate-900 tracking-tight">Broadcast WhatsApp</h1>
        <p class="text-sm text-slate-400 mt-0.5">Riwayat pengiriman notifikasi WhatsApp kepada pendonor</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4">
        <div class="animate-fade-in-up delay-1 bg-white rounded-2xl border border-slate-100 shadow-sm p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Terkirim</span>
            </div>
            <div class="text-3xl font-bold text-slate-900 tracking-tight">{{ $totalSent }}</div>
        </div>

        <div class="animate-fade-in-up delay-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-crimson-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-crimson-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Gagal</span>
            </div>
            <div class="text-3xl font-bold text-slate-900 tracking-tight">{{ $totalFailed }}</div>
        </div>

        <div class="animate-fade-in-up delay-3 bg-white rounded-2xl border border-slate-100 shadow-sm p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Pending</span>
            </div>
            <div class="text-3xl font-bold text-slate-900 tracking-tight">{{ $totalPending }}</div>
        </div>
    </div>

    <!-- Broadcast History Table -->
    <div class="animate-fade-in-up delay-3 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100">
            <h2 class="text-sm font-bold text-slate-900">Riwayat Broadcast</h2>
            <p class="text-xs text-slate-400 mt-0.5">Semua pengiriman WhatsApp yang pernah dilakukan</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr>
                        <th class="text-xs font-semibold text-slate-400 uppercase tracking-wider px-6 py-4 border-b border-slate-100 bg-slate-50/50">Tanggal</th>
                        <th class="text-xs font-semibold text-slate-400 uppercase tracking-wider px-6 py-4 border-b border-slate-100 bg-slate-50/50">Nomor HP</th>
                        <th class="text-xs font-semibold text-slate-400 uppercase tracking-wider px-6 py-4 border-b border-slate-100 bg-slate-50/50">Pesan</th>
                        <th class="text-xs font-semibold text-slate-400 uppercase tracking-wider px-6 py-4 border-b border-slate-100 bg-slate-50/50">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($broadcasts as $broadcast)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 text-sm text-slate-600 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($broadcast->broadcast_date)->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600 font-mono">
                            {{ $broadcast->phone }}
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500 max-w-[300px] truncate">
                            {{ $broadcast->message }}
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusStyles = match($broadcast->status) {
                                    'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'failed' => 'bg-crimson-50 text-crimson-700 border-crimson-200',
                                    default => 'bg-amber-50 text-amber-700 border-amber-200',
                                };
                                $statusLabels = match($broadcast->status) {
                                    'success' => 'Terkirim',
                                    'failed' => 'Gagal',
                                    default => 'Pending',
                                };
                            @endphp
                            <span class="inline-block {{ $statusStyles }} border rounded-lg px-2.5 py-1 text-xs font-semibold">{{ $statusLabels }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <p class="text-base font-bold text-gray-700">Belum ada riwayat broadcast</p>
                                <p class="text-sm text-slate-400 mt-1">Riwayat pengiriman WhatsApp akan muncul di sini setelah ada broadcast yang dikirim.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($broadcasts->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/30">
            <span class="text-sm text-slate-400">Menampilkan {{ $broadcasts->firstItem() }}–{{ $broadcasts->lastItem() }} dari {{ $broadcasts->total() }}</span>
            <div class="flex gap-2">
                @if($broadcasts->onFirstPage())
                    <span class="border border-slate-200 rounded-lg px-3.5 py-1.5 text-sm text-slate-300 cursor-not-allowed">Sebelumnya</span>
                @else
                    <a href="{{ $broadcasts->previousPageUrl() }}" class="border border-slate-200 rounded-lg px-3.5 py-1.5 text-sm text-slate-600 hover:bg-slate-50 transition-colors font-medium">Sebelumnya</a>
                @endif
                @if($broadcasts->hasMorePages())
                    <a href="{{ $broadcasts->nextPageUrl() }}" class="border border-slate-200 rounded-lg px-3.5 py-1.5 text-sm text-slate-600 hover:bg-slate-50 transition-colors font-medium">Selanjutnya</a>
                @else
                    <span class="border border-slate-200 rounded-lg px-3.5 py-1.5 text-sm text-slate-300 cursor-not-allowed">Selanjutnya</span>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
