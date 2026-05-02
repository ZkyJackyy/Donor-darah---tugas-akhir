@extends('layouts.admin')

@section('page_title', 'Permintaan Donor')

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <p class="text-sm text-gray-500 font-medium">Data riwayat dan permintaan darah yang sedang aktif.</p>
    </div>
    
    <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
        <form method="GET" action="{{ route('admin.blood-requests.index') }}" class="relative w-full sm:w-64">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari RS atau lokasi..." class="w-full pl-10 pr-4 py-2 bg-white rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm shadow-sm transition-all">
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </form>
        <a href="{{ route('admin.blood-requests.create') }}" class="inline-flex items-center justify-center bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-150 shadow-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Buat Permintaan
        </a>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-xs font-medium text-gray-500 uppercase tracking-wide border-b border-gray-100">
                    <th class="px-6 py-4">ID</th>
                    <th class="px-6 py-4">Rumah Sakit / Lokasi</th>
                    <th class="px-6 py-4">Kebutuhan</th>
                    <th class="px-6 py-4">Waktu</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Opsi</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100">
                @forelse($bloodRequests as $req)
                <tr class="odd:bg-white even:bg-gray-50/50 hover:bg-gray-100/50 transition-colors">
                    <td class="px-6 py-4 font-bold text-gray-400">#{{ $req->id }}</td>
                    <td class="px-6 py-4">
                        <div class="font-semibold text-gray-800">{{ $req->hospital_name }}</div>
                        <div class="text-[11px] text-gray-400 mt-0.5 truncate max-w-xs">{{ $req->hospital_address }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 bg-red-600 text-white text-[11px] font-bold rounded">
                                {{ $req->blood_type }}{{ $req->rhesus }}
                            </span>
                            <span class="text-xs text-gray-600">{{ $req->required_bags }} Kantong</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-xs font-semibold text-gray-700">{{ $req->created_at->format('d/m/Y') }}</div>
                        <div class="text-[10px] text-gray-400 uppercase tracking-tighter mt-0.5">{{ $req->created_at->format('H:i') }} WIB</div>
                    </td>
                    <td class="px-6 py-4">
                        @if($req->status === 'open')
                            <span class="bg-green-100 text-green-700 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider inline-flex items-center">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5 animate-pulse"></span> Terbuka
                            </span>
                        @elseif($req->status === 'completed')
                            <span class="bg-blue-100 text-blue-700 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider inline-flex items-center">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-1.5"></span> Selesai
                            </span>
                        @else
                            <span class="bg-gray-100 text-gray-500 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider">
                                {{ strtoupper($req->status) }}
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.blood-requests.show', $req->id) }}" class="text-xs font-bold text-gray-400 hover:text-red-600 uppercase tracking-widest transition-colors flex items-center justify-end">
                            Detail
                            <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center text-gray-400">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <p class="text-sm font-medium">Belum ada data permintaan</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($bloodRequests->hasPages())
    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
        {{ $bloodRequests->links() }}
    </div>
    @endif
</div>
@endsection
