@extends('layouts.admin')

@section('page_title', 'Data Pendonor')

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <p class="text-sm text-gray-500 font-medium">Manajemen basis data relawan pendonor darah.</p>
    </div>
    
    <div class="w-full md:w-80">
        <form method="GET" action="{{ route('admin.donors') }}" class="relative">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau nomor HP..." class="w-full pl-10 pr-4 py-2 bg-white rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm transition-all shadow-sm">
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </form>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-xs font-medium text-gray-500 uppercase tracking-wide border-b border-gray-100">
                    <th class="px-6 py-4">Nama Pendonor</th>
                    <th class="px-6 py-4 text-center">Golongan Darah</th>
                    <th class="px-6 py-4">Nomor HP</th>
                    <th class="px-6 py-4">Donor Terakhir</th>
                    <th class="px-6 py-4 text-right">Status</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100">
                @forelse($donors as $donor)
                <tr class="odd:bg-white even:bg-gray-50/50 hover:bg-gray-100/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-3">
                            <div class="h-9 w-9 rounded-full bg-red-100 text-red-600 flex items-center justify-center font-bold text-sm">
                                {{ strtoupper(substr($donor->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="font-semibold text-gray-800">{{ $donor->name }}</div>
                                <div class="text-[11px] text-gray-400 uppercase tracking-tight mt-0.5">Terdaftar: {{ $donor->created_at ? $donor->created_at->format('M Y') : 'Unknown' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex justify-center">
                            <span class="px-3 py-1 bg-red-600 text-white text-xs font-bold rounded-md">
                                {{ $donor->blood_type ?? '?' }}{{ $donor->rhesus ?? '' }}
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 font-medium text-gray-600">
                        {{ $donor->phone }}
                    </td>
                    <td class="px-6 py-4 text-gray-500 text-xs">
                        @if($donor->last_donor_date)
                            <div class="font-semibold text-gray-700">{{ \Carbon\Carbon::parse($donor->last_donor_date)->format('d/m/Y') }}</div>
                            <div class="mt-0.5">{{ \Carbon\Carbon::parse($donor->last_donor_date)->diffForHumans() }}</div>
                        @else
                            <span class="italic text-gray-300 uppercase tracking-widest text-[10px]">Belum Ada Data</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        @if($donor->is_available)
                            <span class="bg-green-100 text-green-700 rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-wider">
                                Siap Donor
                            </span>
                        @else
                            <span class="bg-yellow-100 text-yellow-700 rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-wider">
                                Cooldown
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-16 text-center text-gray-400">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            <p class="text-sm font-medium">Data pendonor tidak ditemukan</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($donors->hasPages())
    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
        {{ $donors->links() }}
    </div>
    @endif
</div>
@endsection
