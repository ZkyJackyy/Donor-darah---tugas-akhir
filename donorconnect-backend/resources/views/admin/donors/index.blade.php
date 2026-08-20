@extends('layouts.admin')

@section('page_title', 'Manajemen Pendonor')

@section('content')
<div class="space-y-6">
    <!-- Header & Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <p class="text-sm text-gray-500 font-medium">Kelola basis data relawan donor darah dengan mudah dan efisien.</p>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white p-4 rounded-lg border border-gray-200 flex flex-col md:flex-row gap-4">
        <form method="GET" action="{{ route('admin.donors') }}" class="flex-1 flex flex-col md:flex-row gap-4">
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, nomor HP, atau NIK pendonor..."
                    class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-md text-sm focus:ring-2 focus:ring-brand-500/20 focus:bg-white transition-colors text-gray-700 font-medium placeholder-gray-400">
                <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <div class="relative">
                <select name="blood_type" onchange="this.form.submit()" class="appearance-none pl-4 pr-10 py-3 bg-white border border-gray-200 rounded-md text-sm font-semibold text-gray-600 focus:outline-none focus:ring-2 focus:ring-brand-500/20 hover:border-brand-300 cursor-pointer min-w-[120px]">
                    <option value="">Gol. Darah</option>
                    <option value="A" {{ request('blood_type') === 'A' ? 'selected' : '' }}>A</option>
                    <option value="B" {{ request('blood_type') === 'B' ? 'selected' : '' }}>B</option>
                    <option value="AB" {{ request('blood_type') === 'AB' ? 'selected' : '' }}>AB</option>
                    <option value="O" {{ request('blood_type') === 'O' ? 'selected' : '' }}>O</option>
                </select>
                <svg class="w-4 h-4 text-gray-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">Profil Pendonor</th>
                        <th class="px-6 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">Kontak</th>
                        <th class="px-6 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200 text-center">Golongan Darah</th>
                        <th class="px-6 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">Riwayat Terakhir</th>
                        <th class="px-6 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">Status Darah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($donors as $donor)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <div class="relative">
                                    <div class="w-9 h-9 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-500 font-semibold">
                                        {{ strtoupper(substr($donor->name, 0, 1)) }}
                                    </div>
                                    @if($donor->is_available)
                                        <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-emerald-500 border-2 border-white rounded-full"></div>
                                    @else
                                        <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-gray-400 border-2 border-white rounded-full"></div>
                                    @endif
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-800">{{ $donor->name }}</div>
                                    <div class="text-[11px] text-gray-400 uppercase tracking-wide mt-0.5">Bergabung {{ $donor->created_at ? $donor->created_at->format('M Y') : '-' }}</div>
                                    <div class="text-[11px] text-gray-400 mt-0.5">
                                        {{ $donor->birth_date ? $donor->birth_date->age . ' th' : '-' }} &bull; {{ $donor->weight ? $donor->weight . ' kg' : '-' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-600 font-mono">{{ $donor->phone }}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center justify-center w-12 h-8 bg-brand-50 text-brand-700 font-semibold rounded-md border border-brand-100">
                                {{ $donor->blood_type ?? '?' }}{{ $donor->rhesus ?? '' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($donor->last_donor_date)
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-700 font-mono">{{ \Carbon\Carbon::parse($donor->last_donor_date)->format('d M Y') }}</div>
                                        <div class="text-[10px] font-medium text-brand-600 mt-0.5">{{ \Carbon\Carbon::parse($donor->last_donor_date)->diffForHumans() }}</div>
                                    </div>
                                </div>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-gray-100 text-gray-500 text-[10px] font-semibold uppercase tracking-wide">
                                    Belum Ada Riwayat
                                </span>
                            @endif
                            <div class="text-[10px] text-gray-400 font-medium mt-1">{{ $donor->donor_histories_count }}x donasi</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($donor->is_available)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Siap Donor
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-amber-50 text-amber-700 text-xs font-semibold border border-amber-100">
                                    <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Masa Cooldown
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                </div>
                                <p class="text-base font-semibold text-gray-700">Tidak ada pendonor ditemukan</p>
                                <p class="text-sm text-gray-400 mt-1">Coba gunakan kata kunci atau filter lain.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($donors->hasPages())
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            {{ $donors->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const initialAvailable = {{ (int) $initialAvailableCount }};
        setInterval(async () => {
            try {
                const res = await fetch('/api/admin-poll/donors');
                if (!res.ok) return;
                const { data } = await res.json();
                if (data.available !== initialAvailable) window.location.reload();
            } catch (e) {}
        }, 15000);
    })();
</script>
@endpush
@endsection
