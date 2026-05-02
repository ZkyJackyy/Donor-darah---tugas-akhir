@extends('layouts.admin')

@section('page_title', 'Laporan & Statistik')

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <p class="text-sm text-gray-500 font-medium">Laporan aktivitas donasi bulanan UDD PMI Kota Padang.</p>
    </div>
    
    <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="flex bg-white rounded-md border border-gray-300 overflow-hidden shadow-sm">
            <select name="month" class="pl-4 pr-8 py-2 bg-transparent text-sm font-medium text-gray-700 focus:outline-none border-r border-gray-100 cursor-pointer appearance-none">
                @for($m=1; $m<=12; $m++)
                    <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                @endfor
            </select>
            <select name="year" class="pl-4 pr-8 py-2 bg-transparent text-sm font-medium text-gray-700 focus:outline-none cursor-pointer appearance-none">
                @for($y=date('Y'); $y>=2020; $y--)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 text-sm font-bold uppercase tracking-wider transition">
                Filter
            </button>
        </form>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-100">
        <div class="flex items-center space-x-3 mb-3">
            <div class="p-2 bg-red-100 text-red-600 rounded-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">Donasi Berhasil</span>
        </div>
        <div class="text-2xl font-bold text-gray-800">{{ $totalSuccessfulDonors }}</div>
    </div>

    <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-100">
        <div class="flex items-center space-x-3 mb-3">
            <div class="p-2 bg-blue-100 text-blue-600 rounded-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">Total Permintaan</span>
        </div>
        <div class="text-2xl font-bold text-gray-800">{{ $totalRequests }}</div>
    </div>

    <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-100">
        <div class="flex items-center space-x-3 mb-3">
            <div class="p-2 bg-orange-100 text-orange-600 rounded-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            </div>
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">Kantong Darah</span>
        </div>
        <div class="text-2xl font-bold text-gray-800">{{ $totalBagsRequested }}</div>
    </div>

    <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-100">
        <div class="flex items-center space-x-3 mb-3">
            <div class="p-2 bg-green-100 text-green-600 rounded-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">Req. Selesai</span>
        </div>
        <div class="text-2xl font-bold text-gray-800">{{ $completedRequests }}</div>
    </div>
</div>

<!-- History Table -->
<div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
        <h3 class="text-xs font-bold text-gray-800 uppercase tracking-widest">Riwayat Verifikasi Donasi</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/50 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">
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
                        <div class="font-bold text-gray-800">{{ $history->user->name }}</div>
                        <div class="text-[11px] text-gray-400">{{ $history->user->phone }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="px-2 py-0.5 bg-red-50 text-red-700 text-[10px] font-bold rounded border border-red-100">
                            {{ $history->user->blood_type }}{{ $history->user->rhesus }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-xs font-medium text-gray-700">{{ $history->location_name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <a href="{{ route('admin.blood-requests.show', $history->blood_request_id) }}" class="text-xs font-bold text-red-600 hover:underline uppercase tracking-tighter">
                            #{{ $history->blood_request_id }}
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-16 text-center text-gray-400 opacity-60">
                        <div class="flex flex-col items-center">
                            <svg class="w-10 h-10 mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <p class="text-[11px] font-bold uppercase tracking-widest">Belum ada riwayat donor bulan ini</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
