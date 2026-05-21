@extends('layouts.admin')

@section('page_title', 'Permintaan Darah')

@section('content')
<div class="space-y-6">
    <!-- Header & Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <p class="text-sm text-gray-500 font-medium">Pantau dan kelola permintaan kantong darah dari berbagai rumah sakit.</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <form method="GET" action="{{ route('admin.blood-requests.index') }}" class="relative w-full sm:w-72">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari RS atau lokasi..." class="w-full pl-11 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all shadow-sm">
                <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </form>
            <a href="{{ route('admin.blood-requests.create') }}" class="inline-flex items-center justify-center bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition duration-200 shadow-lg shadow-brand-500/30 whitespace-nowrap">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Buat Permintaan
            </a>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/80">
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 w-16">ID</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">Instansi / Lokasi</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">Kebutuhan</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">Prioritas & Waktu</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">Status</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-right">Opsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($bloodRequests as $req)
                    <tr class="hover:bg-gray-50/50 transition-colors group">
                        <td class="px-6 py-5 font-bold text-gray-400 text-sm">#{{ $req->id }}</td>
                        <td class="px-6 py-5">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0 mt-0.5 border border-blue-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-800 leading-tight">{{ $req->hospital_name }}</div>
                                    <div class="text-[11px] text-gray-400 mt-1 line-clamp-1 max-w-[200px]" title="{{ $req->hospital_address }}">{{ $req->hospital_address }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-2">
                                <div class="relative">
                                    <span class="inline-flex items-center justify-center w-10 h-10 bg-brand-600 text-white font-extrabold rounded-lg shadow-sm shadow-brand-500/20 text-sm">
                                        {{ $req->blood_type }}{{ $req->rhesus }}
                                    </span>
                                </div>
                                <div class="text-sm font-bold text-gray-600">× {{ $req->required_bags }} <span class="text-xs font-medium text-gray-400">Kantong</span></div>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <!-- Mock Urgency Logic since it's not in DB yet -->
                            @php
                                $urgency = $req->required_bags >= 5 ? 'critical' : ($req->required_bags >= 3 ? 'urgent' : 'normal');
                            @endphp
                            
                            @if($urgency == 'critical')
                                <span class="inline-flex items-center gap-1.5 bg-red-100 text-red-700 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider mb-2">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    Darurat
                                </span>
                            @elseif($urgency == 'urgent')
                                <span class="inline-flex items-center gap-1.5 bg-orange-100 text-orange-700 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider mb-2">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    Penting
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 bg-blue-100 text-blue-700 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider mb-2">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Normal
                                </span>
                            @endif
                            <div class="text-xs font-semibold text-gray-700">{{ $req->created_at->format('d/m/Y') }}</div>
                            <div class="text-[10px] text-gray-400 mt-0.5">{{ $req->created_at->format('H:i') }} WIB</div>
                        </td>
                        <td class="px-6 py-5">
                            @if($req->status === 'open')
                                <div class="flex items-center gap-2">
                                    <span class="relative flex h-2.5 w-2.5">
                                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                      <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                                    </span>
                                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Terbuka</span>
                                </div>
                                <!-- Progress Bar Mock -->
                                <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2">
                                  <div class="bg-green-500 h-1.5 rounded-full" style="width: 45%"></div>
                                </div>
                                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1 text-right">Proses Pencarian</div>
                            @elseif($req->status === 'completed')
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Selesai</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2">
                                  <div class="bg-blue-500 h-1.5 rounded-full" style="width: 100%"></div>
                                </div>
                                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1 text-right">Kebutuhan Terpenuhi</div>
                            @else
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">{{ strtoupper($req->status) }}</span>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-5 text-right">
                            <a href="{{ route('admin.blood-requests.show', $req->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 hover:bg-brand-50 text-gray-600 hover:text-brand-600 rounded-lg text-xs font-bold uppercase tracking-wider transition-colors border border-gray-100 hover:border-brand-200">
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
                                <p class="text-base font-bold text-gray-700">Belum ada permintaan aktif</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bloodRequests->hasPages())
        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100">
            {{ $bloodRequests->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
