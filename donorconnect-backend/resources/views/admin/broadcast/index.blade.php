@extends('layouts.admin')

@section('page_title', 'Broadcast WhatsApp')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="font-semibold text-2xl text-gray-900 tracking-tight">Broadcast WhatsApp</h1>
        <p class="text-sm text-gray-400 mt-0.5">Riwayat pengiriman notifikasi WhatsApp kepada pendonor</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 border-l-2 border-l-emerald-600 p-5">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Terkirim</span>
            <div class="text-3xl font-semibold text-gray-900 tracking-tight font-mono mt-2">{{ $totalSent }}</div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 border-l-2 border-l-brand-600 p-5">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Gagal</span>
            <div class="text-3xl font-semibold text-gray-900 tracking-tight font-mono mt-2">{{ $totalFailed }}</div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 border-l-2 border-l-amber-600 p-5">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Pending</span>
            <div class="text-3xl font-semibold text-gray-900 tracking-tight font-mono mt-2">{{ $totalPending }}</div>
        </div>
    </div>

    <!-- Broadcast History Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Riwayat Broadcast</h2>
            <p class="text-xs text-gray-400 mt-0.5">Semua pengiriman WhatsApp yang pernah dilakukan</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr>
                        <th class="text-xs font-semibold text-gray-500 uppercase tracking-wide px-6 py-3 border-b border-gray-200 bg-gray-50">Tanggal</th>
                        <th class="text-xs font-semibold text-gray-500 uppercase tracking-wide px-6 py-3 border-b border-gray-200 bg-gray-50">Nomor HP</th>
                        <th class="text-xs font-semibold text-gray-500 uppercase tracking-wide px-6 py-3 border-b border-gray-200 bg-gray-50">Pesan</th>
                        <th class="text-xs font-semibold text-gray-500 uppercase tracking-wide px-6 py-3 border-b border-gray-200 bg-gray-50">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($broadcasts as $broadcast)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($broadcast->broadcast_date)->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 font-mono">
                            {{ $broadcast->phone }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-[300px] truncate">
                            {{ $broadcast->message }}
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusStyles = match($broadcast->status) {
                                    'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'failed' => 'bg-brand-50 text-brand-700 border-brand-200',
                                    default => 'bg-amber-50 text-amber-700 border-amber-200',
                                };
                                $statusLabels = match($broadcast->status) {
                                    'success' => 'Terkirim',
                                    'failed' => 'Gagal',
                                    default => 'Pending',
                                };
                            @endphp
                            <span class="inline-block {{ $statusStyles }} border rounded px-2.5 py-1 text-xs font-semibold">{{ $statusLabels }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <p class="text-base font-semibold text-gray-700">Belum ada riwayat broadcast</p>
                                <p class="text-sm text-gray-400 mt-1">Riwayat pengiriman WhatsApp akan muncul di sini setelah ada broadcast yang dikirim.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($broadcasts->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50">
            <span class="text-sm text-gray-400">Menampilkan {{ $broadcasts->firstItem() }}–{{ $broadcasts->lastItem() }} dari {{ $broadcasts->total() }}</span>
            <div class="flex gap-2">
                @if($broadcasts->onFirstPage())
                    <span class="border border-gray-200 rounded-md px-3.5 py-1.5 text-sm text-gray-300 cursor-not-allowed">Sebelumnya</span>
                @else
                    <a href="{{ $broadcasts->previousPageUrl() }}" class="border border-gray-200 rounded-md px-3.5 py-1.5 text-sm text-gray-600 hover:bg-gray-50 transition-colors font-medium">Sebelumnya</a>
                @endif
                @if($broadcasts->hasMorePages())
                    <a href="{{ $broadcasts->nextPageUrl() }}" class="border border-gray-200 rounded-md px-3.5 py-1.5 text-sm text-gray-600 hover:bg-gray-50 transition-colors font-medium">Selanjutnya</a>
                @else
                    <span class="border border-gray-200 rounded-md px-3.5 py-1.5 text-sm text-gray-300 cursor-not-allowed">Selanjutnya</span>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const latestId = {{ (int) $latestLogId }};
        setInterval(async () => {
            try {
                const res = await fetch('/api/admin-poll/broadcast');
                if (!res.ok) return;
                const { data } = await res.json();
                if (data.latest_id !== latestId) window.location.reload();
            } catch (e) {}
        }, 15000);
    })();
</script>
@endpush
@endsection
