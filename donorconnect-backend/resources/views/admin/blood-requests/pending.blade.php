@extends('layouts.admin')

@section('page_title', 'Pengajuan Keluarga')

@section('content')
<div class="space-y-6" x-data="pendingWatcher({{ $bloodRequests->total() }})">
    <div>
        <p class="text-sm text-gray-500 font-medium">Pengajuan permintaan donor pengganti dari user untuk keluarga — validasi sebelum masuk alur pencarian pendonor.</p>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">Pemohon</th>
                        <th class="px-6 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">Pasien</th>
                        <th class="px-6 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">Kebutuhan</th>
                        <th class="px-6 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">RS Tujuan</th>
                        <th class="px-6 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">Surat Rujukan</th>
                        <th class="px-6 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">Diajukan</th>
                        <th class="px-6 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($bloodRequests as $req)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-800">{{ $req->requestedBy->name ?? '-' }}</div>
                            <div class="text-[11px] text-gray-400 font-mono">{{ $req->requestedBy->phone ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-700">{{ $req->patient_name }}</div>
                            <div class="text-[11px] text-gray-400">{{ $req->patient_relationship }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-gray-700">
                                {{ $req->blood_type }}{{ $req->rhesus }} × {{ $req->required_bags }} <span class="text-xs font-medium text-gray-400">Kantong</span>
                            </div>
                            <div class="text-[11px] text-gray-400 uppercase tracking-wide mt-0.5">{{ $req->urgency_level }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-700">{{ $req->hospital_name }}</div>
                            <div class="text-[11px] text-gray-400 mt-0.5 line-clamp-1 max-w-[200px]" title="{{ $req->hospital_address }}">{{ $req->hospital_address }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($req->referral_letter_url)
                            <a href="{{ $req->referral_letter_url }}" target="_blank" rel="noopener">
                                <img src="{{ $req->referral_letter_url }}" alt="Surat rujukan" class="w-12 h-12 object-cover rounded-md border border-gray-200 hover:opacity-80 transition-opacity">
                            </a>
                            @else
                            <span class="text-[11px] text-gray-400 italic">Tidak ada</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs font-medium text-gray-700 font-mono">{{ $req->created_at->format('d/m/Y') }}</div>
                            <div class="text-[10px] text-gray-400 mt-0.5 font-mono">{{ $req->created_at->format('H:i') }} WIB</div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <form action="{{ route('admin.blood-requests.approve', $req->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-xs font-semibold uppercase tracking-wide transition-colors">
                                        Setujui
                                    </button>
                                </form>
                                <button type="button" @click="rejectModal = true; rejectId = {{ $req->id }}" class="px-3 py-1.5 border border-gray-200 text-gray-600 hover:text-red-600 hover:border-red-200 rounded-md text-xs font-semibold uppercase tracking-wide transition-colors">
                                    Tolak
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <p class="text-base font-semibold text-gray-700">Tidak ada pengajuan menunggu</p>
                                <p class="text-sm text-gray-400 mt-1">Pengajuan baru dari user akan muncul di sini.</p>
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

    <!-- Reject Modal -->
    <div x-show="rejectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60" x-cloak x-transition>
        <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full m-4 border border-gray-200" @click.away="rejectModal = false">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Tolak Pengajuan</h3>
            <p class="text-sm text-gray-600 mb-4 leading-relaxed">Jelaskan alasan penolakan — user akan melihat alasan ini di layar "Permintaan Saya".</p>

            <form :action="`/admin/blood-requests/${rejectId}/reject`" method="POST" class="flex flex-col gap-3">
                @csrf
                <textarea name="rejection_reason" rows="3" required maxlength="500" placeholder="Mis. Data pasien tidak lengkap, silakan hubungi PMI langsung."
                    class="w-full bg-gray-50 border border-gray-200 rounded-md px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500"></textarea>
                <button type="submit" class="w-full py-3 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-semibold uppercase tracking-wide transition-colors">Tolak Pengajuan</button>
                <button type="button" @click="rejectModal = false" class="w-full py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md text-sm font-semibold uppercase tracking-wide transition-colors">Batalkan</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pendingWatcher', (initialTotal) => ({
        rejectModal: false,
        rejectId: null,
        init() {
            setInterval(async () => {
                try {
                    const res = await fetch('/api/admin-poll/blood-requests/pending-count');
                    if (!res.ok) return;
                    const { data: { count } } = await res.json();
                    if (count !== initialTotal) window.location.reload();
                } catch (e) {}
            }, 15000);
        }
    }));
});
</script>
@endpush
@endsection
