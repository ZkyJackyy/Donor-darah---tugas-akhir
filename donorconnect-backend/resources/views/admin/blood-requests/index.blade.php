@extends('layouts.admin')

@section('page_title', 'Permintaan Darah')

@section('content')
<div class="space-y-6" x-data='statusWatcher(@json($bloodRequests->pluck("status", "id")))'>
    <!-- Header & Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <p class="text-sm text-gray-500 font-medium">Pantau dan kelola permintaan kantong darah dari berbagai rumah sakit.</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <form method="GET" action="{{ route('admin.blood-requests.index') }}" class="relative w-full sm:w-72">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari RS atau lokasi..." class="w-full pl-11 pr-4 py-2.5 bg-white border border-gray-200 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </form>
            <a href="{{ route('admin.blood-requests.create') }}" class="inline-flex items-center justify-center bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-md text-sm font-semibold transition-colors whitespace-nowrap">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Buat Permintaan
            </a>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200 w-16">ID</th>
                        <th class="px-6 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">Instansi / Lokasi</th>
                        <th class="px-6 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">Kebutuhan</th>
                        <th class="px-6 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">Prioritas & Waktu</th>
                        <th class="px-6 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">Status</th>
                        <th class="px-6 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200 text-right">Opsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($bloodRequests as $req)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-mono text-gray-400 text-sm">{{ ($bloodRequests->currentPage() - 1) * $bloodRequests->perPage() + $loop->iteration }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2 mb-1">
                                @if($req->type === 'event')
                                    <span class="inline-flex items-center gap-1 text-violet-700 text-[10px] font-semibold uppercase tracking-wide">Event Terbuka</span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-gray-500 text-[10px] font-semibold uppercase tracking-wide">Darurat</span>
                                @endif
                            </div>
                            <div class="font-semibold text-gray-800 leading-tight">{{ $req->hospital_name }}</div>
                            <div class="text-[11px] text-gray-400 mt-1 line-clamp-1 max-w-[200px]" title="{{ $req->hospital_address }}">{{ $req->hospital_address }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($req->type === 'event')
                                <div class="text-sm font-semibold text-gray-700">Semua Golongan</div>
                                <div class="text-xs font-medium text-gray-400">{{ $req->required_bags ? "Target {$req->required_bags} kantong" : 'Tanpa target' }}</div>
                            @else
                                <div class="text-sm font-semibold text-gray-700">
                                    {{ $req->blood_type }}{{ $req->rhesus }} × {{ $req->required_bags }} <span class="text-xs font-medium text-gray-400">Kantong</span>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($req->urgency_level == 'critical')
                                <span class="inline-flex items-center gap-1.5 text-red-700 text-[11px] font-semibold uppercase tracking-wide mb-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                                    Darurat
                                </span>
                            @elseif($req->urgency_level == 'urgent')
                                <span class="inline-flex items-center gap-1.5 text-amber-700 text-[11px] font-semibold uppercase tracking-wide mb-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                    Penting
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-gray-500 text-[11px] font-semibold uppercase tracking-wide mb-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                    Normal
                                </span>
                            @endif
                            <div class="text-xs font-medium text-gray-700 font-mono">{{ $req->created_at->format('d/m/Y') }}</div>
                            <div class="text-[10px] text-gray-400 mt-0.5 font-mono">{{ $req->created_at->format('H:i') }} WIB</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($req->status === 'open')
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Terbuka</span>
                                </div>
                                @php
                                    $fulfilledPercent = $req->required_bags > 0
                                        ? min(100, round($req->verified_candidates_count / $req->required_bags * 100))
                                        : 0;
                                @endphp
                                <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2">
                                  <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $fulfilledPercent }}%"></div>
                                </div>
                                <div class="text-[9px] font-medium text-gray-400 uppercase tracking-wide mt-1 text-right font-mono">{{ $req->verified_candidates_count }}/{{ $req->required_bags }} Kantong Terverifikasi</div>
                            @elseif($req->status === 'fulfilled')
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                                    <span class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Selesai</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2">
                                  <div class="bg-sky-500 h-1.5 rounded-full" style="width: 100%"></div>
                                </div>
                                <div class="text-[9px] font-medium text-gray-400 uppercase tracking-wide mt-1 text-right">Kebutuhan Terpenuhi</div>
                            @else
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ strtoupper($req->status) }}</span>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.blood-requests.show', $req->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-gray-600 hover:text-brand-600 rounded-md text-xs font-semibold uppercase tracking-wide transition-colors border border-gray-200 hover:border-brand-200">
                                Detail
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <p class="text-base font-semibold text-gray-700">Belum ada permintaan aktif</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bloodRequests->hasPages())
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            {{ $bloodRequests->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('statusWatcher', (initialStatuses) => ({
        init() {
            const ids = Object.keys(initialStatuses);
            if (ids.length === 0) return;

            setInterval(async () => {
                try {
                    const res = await fetch(`/api/admin-poll/blood-requests/statuses?ids=${ids.join(',')}`);
                    if (!res.ok) return;
                    const current = await res.json();
                    const changed = ids.some(id => current[id] !== initialStatuses[id]);
                    if (changed) window.location.reload();
                } catch (e) {}
            }, 30000);
        }
    }));
});
</script>
@endpush
@endsection
