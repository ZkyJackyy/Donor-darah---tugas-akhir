@extends('layouts.admin')

@section('page_title', 'Laporan & Statistik')

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <p class="text-sm text-gray-500 font-medium">Laporan aktivitas donasi bulanan UDD PMI Kota Padang.</p>
    </div>
    
    <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="flex bg-white rounded-md border border-gray-200 overflow-hidden">
            <select name="month" class="pl-4 pr-8 py-2 bg-transparent text-sm font-medium text-gray-700 focus:outline-none border-r border-gray-200 cursor-pointer appearance-none">
                @for($m=1; $m<=12; $m++)
                    <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                @endfor
            </select>
            <select name="year" class="pl-4 pr-8 py-2 bg-transparent text-sm font-medium text-gray-700 focus:outline-none cursor-pointer appearance-none">
                @for($y=date('Y'); $y>=2020; $y--)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-5 py-2 text-sm font-semibold uppercase tracking-wide transition-colors">
                Filter
            </button>
        </form>
        <a href="{{ route('admin.reports.pdf', ['month' => $month, 'year' => $year]) }}" target="_blank" class="inline-flex items-center justify-center bg-gray-800 hover:bg-gray-900 text-white px-5 py-2 rounded-md text-sm font-semibold transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            Export PDF
        </a>
    </div>
</div>

<!-- Section: Permintaan (Demand) -->
<div class="mb-3 flex items-center gap-2">
    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Permintaan Darah (Kebutuhan)</h2>
</div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg p-6 border border-gray-200 border-l-2 border-l-sky-600">
        <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Permintaan Dibuat</span>
        <div class="text-2xl font-semibold text-gray-800 font-mono mt-2">{{ $totalRequests }}</div>
        <p class="text-[11px] text-gray-400 mt-1">Jumlah permintaan darah baru bulan ini</p>
    </div>

    <div class="bg-white rounded-lg p-6 border border-gray-200 border-l-2 border-l-amber-600">
        <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Kantong Diminta</span>
        <div class="text-2xl font-semibold text-gray-800 font-mono mt-2">{{ $totalBagsRequested }}</div>
        <p class="text-[11px] text-gray-400 mt-1">Total kebutuhan kantong dari semua permintaan bulan ini</p>
    </div>

    <div class="bg-white rounded-lg p-6 border border-gray-200 border-l-2 border-l-violet-600">
        <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Permintaan Fulfilled</span>
        <div class="text-2xl font-semibold text-gray-800 font-mono mt-2">{{ $completedRequests }}</div>
        <p class="text-[11px] text-gray-400 mt-1">Permintaan (dibuat bulan ini) yang kuotanya sudah tercapai</p>
    </div>
</div>

<!-- Section: Realisasi (Supply) -->
<div class="mb-3 flex items-center gap-2">
    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Realisasi Donasi</h2>
</div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg p-6 border border-gray-200 border-l-2 border-l-brand-600">
        <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Donasi Terverifikasi</span>
        <div class="text-2xl font-semibold text-gray-800 font-mono mt-2">{{ $totalSuccessfulDonors }}</div>
        <p class="text-[11px] text-gray-400 mt-1">Jumlah pendonor yang hadir & diverifikasi bulan ini</p>
    </div>

    <div class="bg-white rounded-lg p-6 border border-gray-200 border-l-2 border-l-emerald-600">
        <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Kantong Terpenuhi</span>
        <div class="text-2xl font-semibold text-gray-800 font-mono mt-2">{{ $totalBagsFulfilled }}</div>
        <p class="text-[11px] text-gray-400 mt-1">1 donasi terverifikasi = 1 kantong (dari {{ $totalBagsRequested }} diminta)</p>
    </div>

    <div class="bg-white rounded-lg p-6 border border-gray-200 border-l-2 border-l-gray-400">
        <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Tingkat Pemenuhan</span>
        <div class="text-2xl font-semibold text-gray-800 font-mono mt-2">{{ $fulfillmentRate }}%</div>
        <p class="text-[11px] text-gray-400 mt-1">Kantong terpenuhi ÷ kantong diminta</p>
    </div>
</div>

<!-- Breakdown Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h3 class="text-xs font-semibold text-gray-800 uppercase tracking-wide">Donasi per Golongan Darah</h3>
        </div>
        <div class="p-6 space-y-3">
            @forelse($bloodTypeBreakdown as $row)
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-700">{{ $row->blood_type }}</span>
                <span class="text-sm font-semibold text-gray-800 font-mono">{{ $row->count }}</span>
            </div>
            @empty
            <p class="text-xs text-gray-400 text-center py-4">Belum ada data bulan ini</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h3 class="text-xs font-semibold text-gray-800 uppercase tracking-wide">Permintaan per Tingkat Urgensi</h3>
        </div>
        <div class="p-6 space-y-3">
            @forelse($urgencyBreakdown as $row)
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-700 uppercase">{{ $row->urgency_level }}</span>
                <span class="text-sm font-semibold text-gray-800 font-mono">{{ $row->count }}</span>
            </div>
            @empty
            <p class="text-xs text-gray-400 text-center py-4">Belum ada data bulan ini</p>
            @endforelse
        </div>
    </div>
</div>

<!-- History Table -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
        <h3 class="text-xs font-semibold text-gray-800 uppercase tracking-wide">Riwayat Verifikasi Donasi</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50 text-[10px] font-semibold text-gray-400 uppercase tracking-wide border-b border-gray-200">
                    <th class="px-6 py-4">Tanggal</th>
                    <th class="px-6 py-4">Pendonor</th>
                    <th class="px-6 py-4 text-center">Golongan</th>
                    <th class="px-6 py-4">Lokasi / RS</th>
                    <th class="px-6 py-4 text-right">Ref. Req</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-50">
                @forelse($histories as $history)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-xs font-semibold text-gray-600">
                        {{ \Carbon\Carbon::parse($history->donor_date)->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-semibold text-gray-800">{{ $history->user->name }}</div>
                        <div class="text-[11px] text-gray-400 font-mono">{{ $history->user->phone }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="px-2 py-0.5 bg-brand-50 text-brand-700 text-[10px] font-semibold rounded border border-brand-100">
                            {{ $history->user->blood_type }}{{ $history->user->rhesus }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-xs font-medium text-gray-700">{{ $history->location_name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <a href="{{ route('admin.blood-requests.show', $history->blood_request_id) }}" class="text-xs font-bold text-brand-600 hover:underline uppercase tracking-tighter">
                            #{{ $history->blood_request_id }}
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-16 text-center text-gray-400 opacity-60">
                        <div class="flex flex-col items-center">
                            <svg class="w-10 h-10 mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <p class="text-[11px] font-semibold uppercase tracking-wide">Belum ada riwayat donor bulan ini</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
