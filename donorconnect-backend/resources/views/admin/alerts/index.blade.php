@extends('layouts.admin')

@section('page_title', 'Peringatan Sistem')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="font-semibold text-2xl text-gray-900 tracking-tight">Peringatan Sistem</h1>
            <p class="text-sm text-gray-400 mt-0.5">Notifikasi yang butuh perhatian admin, mis. seluruh kandidat pendonor menolak.</p>
        </div>
        @if($alerts->contains(fn($alert) => !$alert->read_at))
        <form method="POST" action="{{ route('admin.alerts.read-all') }}">
            @csrf
            <button type="submit" class="inline-flex items-center justify-center bg-white hover:bg-gray-50 text-gray-600 border border-gray-200 px-4 py-2 rounded-md text-sm font-semibold transition-colors whitespace-nowrap">
                Tandai Semua Dibaca
            </button>
        </form>
        @endif
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr>
                        <th class="text-xs font-semibold text-gray-500 uppercase tracking-wide px-6 py-3 border-b border-gray-200 bg-gray-50">Waktu</th>
                        <th class="text-xs font-semibold text-gray-500 uppercase tracking-wide px-6 py-3 border-b border-gray-200 bg-gray-50">Pesan</th>
                        <th class="text-xs font-semibold text-gray-500 uppercase tracking-wide px-6 py-3 border-b border-gray-200 bg-gray-50">Status</th>
                        <th class="text-xs font-semibold text-gray-500 uppercase tracking-wide px-6 py-3 border-b border-gray-200 bg-gray-50 text-right">Opsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($alerts as $alert)
                    <tr class="hover:bg-gray-50 transition-colors {{ !$alert->read_at ? 'bg-red-50/30' : '' }}">
                        <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                            {{ $alert->created_at->format('d M Y, H:i') }} WIB
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 max-w-[480px]">
                            {{ $alert->message }}
                            @if($alert->bloodRequest)
                            <a href="{{ route('admin.blood-requests.show', $alert->blood_request_id) }}" class="block text-xs text-brand-600 font-semibold mt-1 hover:underline">Lihat Permintaan #{{ $alert->blood_request_id }} &rarr;</a>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($alert->read_at)
                                <span class="inline-block bg-gray-50 text-gray-500 border border-gray-200 rounded px-2.5 py-1 text-xs font-semibold">Sudah Dibaca</span>
                            @else
                                <span class="inline-block bg-red-50 text-red-700 border border-red-200 rounded px-2.5 py-1 text-xs font-semibold">Belum Dibaca</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @unless($alert->read_at)
                            <form method="POST" action="{{ route('admin.alerts.read', $alert->id) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-gray-600 hover:text-brand-600 rounded-md text-xs font-semibold uppercase tracking-wide transition-colors border border-gray-200 hover:border-brand-200">
                                    Tandai Dibaca
                                </button>
                            </form>
                            @endunless
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <p class="text-base font-semibold text-gray-700">Tidak ada peringatan</p>
                                <p class="text-sm text-gray-400 mt-1">Peringatan sistem akan muncul di sini kalau ada kejadian yang butuh perhatian admin.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($alerts->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50">
            <span class="text-sm text-gray-400">Menampilkan {{ $alerts->firstItem() }}–{{ $alerts->lastItem() }} dari {{ $alerts->total() }}</span>
            <div class="flex gap-2">
                @if($alerts->onFirstPage())
                    <span class="border border-gray-200 rounded-md px-3.5 py-1.5 text-sm text-gray-300 cursor-not-allowed">Sebelumnya</span>
                @else
                    <a href="{{ $alerts->previousPageUrl() }}" class="border border-gray-200 rounded-md px-3.5 py-1.5 text-sm text-gray-600 hover:bg-gray-50 transition-colors font-medium">Sebelumnya</a>
                @endif
                @if($alerts->hasMorePages())
                    <a href="{{ $alerts->nextPageUrl() }}" class="border border-gray-200 rounded-md px-3.5 py-1.5 text-sm text-gray-600 hover:bg-gray-50 transition-colors font-medium">Selanjutnya</a>
                @else
                    <span class="border border-gray-200 rounded-md px-3.5 py-1.5 text-sm text-gray-300 cursor-not-allowed">Selanjutnya</span>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
