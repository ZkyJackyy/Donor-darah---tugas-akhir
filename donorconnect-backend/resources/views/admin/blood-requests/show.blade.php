@extends('layouts.admin')

@section('page_title', 'Detail Permintaan')

@section('content')
<div x-data="bloodRequestManager({{ $bloodRequest->id }})" class="space-y-8">

    <!-- Header Section -->
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-red-100 text-red-600 rounded-lg flex items-center justify-center font-bold text-xl">
                {{ $bloodRequest->blood_type }}{{ $bloodRequest->rhesus }}
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-xl font-bold text-gray-800">Permintaan #{{ $bloodRequest->id }}</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                          :class="{
                              'bg-green-100 text-green-700': status === 'open',
                              'bg-blue-100 text-blue-700': status === 'fulfilled',
                              'bg-gray-200 text-gray-600': status !== 'open' && status !== 'fulfilled'
                          }"
                          x-text="status"></span>
                </div>
                <p class="text-sm text-gray-500 font-medium">{{ $bloodRequest->hospital_name }} • {{ $bloodRequest->required_bags }} Kantong</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.blood-requests.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-md text-sm font-medium transition duration-150">
                Kembali
            </a>
            <button @click="openWaModal = true" :disabled="status !== 'open'" :class="status !== 'open' ? 'opacity-50 cursor-not-allowed' : ''" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm font-medium transition duration-150 flex items-center shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                Siarkan via WA
            </button>
            <a href="{{ route('admin.blood-requests.pdf', $bloodRequest->id) }}" class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white rounded-md text-sm font-medium transition duration-150 flex items-center shadow-sm" target="_blank">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                Unduh PDF
            </a>
        </div>
    </div>

    <!-- Info Panel -->
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Batas Waktu</p>
            <p class="text-sm font-bold text-gray-800">{{ $bloodRequest->deadline ? $bloodRequest->deadline->format('d M Y, H:i') . ' WIB' : '-' }}</p>
        </div>
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Alamat Rumah Sakit</p>
            <p class="text-sm font-bold text-gray-800">{{ $bloodRequest->hospital_address ?: '-' }}</p>
        </div>
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Dibuat Oleh</p>
            <p class="text-sm font-bold text-gray-800">{{ $bloodRequest->admin->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Catatan</p>
            <p class="text-sm font-bold text-gray-800">{{ $bloodRequest->notes ?: '-' }}</p>
        </div>
    </div>

    <!-- Modals -->
    <div x-show="openWaModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm" x-cloak x-transition>
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-md w-full m-4 border border-gray-100" @click.away="openWaModal = false">
            <h3 class="text-lg font-bold text-gray-800 mb-2">Konfirmasi Penyiaran</h3>
            <p class="text-sm text-gray-600 mb-6 leading-relaxed">System akan mengirimkan notifikasi WhatsApp ke semua pendonor yang memenuhi syarat dalam radius <span class="font-bold text-red-600">5KM</span> dari lokasi rumah sakit.</p>
            
            <form action="{{ route('admin.blood-requests.notify', $bloodRequest->id) }}" method="POST" class="flex flex-col gap-3">
                @csrf
                <button type="submit" @click="isBlasting = true; openWaModal = false" class="w-full py-3 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-bold uppercase tracking-wider transition-colors shadow-sm">Ya, Kirim Sekarang</button>
                <button type="button" @click="openWaModal = false" class="w-full py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md text-sm font-bold uppercase tracking-wider transition-colors">Batalkan</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main List Section -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Kandidat Pendonor</h3>
                    <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">
                        Auto-Update 30s
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50">
                                <th class="px-6 py-4">Pendonor</th>
                                <th class="px-6 py-4 text-center">Jarak</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Waktu</th>
                                <th class="px-6 py-4 text-center">Skrining</th>
                                <th class="px-6 py-4 text-right">Opsi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-50">
                            <template x-for="candidate in activeCandidates" :key="candidate.id">
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-gray-800" x-text="candidate.user.name"></div>
                                        <div class="text-[11px] text-gray-400 font-medium" x-text="candidate.user.phone"></div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-xs font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded" x-text="parseFloat(candidate.distance_km).toFixed(2) + ' KM'"></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 text-[10px] font-bold rounded-full uppercase tracking-wider border"
                                              :class="{
                                                  'bg-yellow-50 text-yellow-700 border-yellow-100': candidate.status === 'notified',
                                                  'bg-blue-50 text-blue-700 border-blue-100': candidate.status === 'confirmed',
                                                  'bg-green-100 text-green-700 border-green-200': candidate.status === 'verified',
                                                  'bg-red-50 text-red-700 border-red-100': candidate.status === 'declined'
                                              }">
                                            <span x-text="candidate.status"></span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-[10px] text-gray-500 font-medium leading-relaxed">
                                        <div x-show="candidate.notified_at">Notif: <span x-text="candidate.notified_at ? new Date(candidate.notified_at).toLocaleString('id-ID') : ''"></span></div>
                                        <div x-show="candidate.confirmed_at">Konfirmasi: <span x-text="candidate.confirmed_at ? new Date(candidate.confirmed_at).toLocaleString('id-ID') : ''"></span></div>
                                        <div x-show="candidate.verified_at">Verifikasi: <span x-text="candidate.verified_at ? new Date(candidate.verified_at).toLocaleString('id-ID') : ''"></span></div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <template x-if="candidate.screening">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full"
                                                  :class="(candidate.screening.health_status && candidate.screening.min_weight && candidate.screening.no_medicine && candidate.screening.not_pregnant) ? 'bg-green-100 text-green-700' : 'bg-red-50 text-red-700'"
                                                  x-text="(candidate.screening.health_status && candidate.screening.min_weight && candidate.screening.no_medicine && candidate.screening.not_pregnant) ? 'Lolos' : 'Tidak Lolos'"></span>
                                        </template>
                                        <span x-show="!candidate.screening" class="text-[10px] text-gray-300 font-medium">-</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <template x-if="candidate.status !== 'verified'">
                                            <form :action="`/admin/blood-requests/verify/${candidate.id}`" method="POST">
                                                @csrf
                                                <button type="submit" class="text-[10px] font-bold text-red-600 hover:text-red-800 uppercase tracking-widest underline">Verifikasi</button>
                                            </form>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div x-show="activeCandidates.length === 0" class="px-6 py-12 text-center text-gray-400 opacity-60">
                        <svg class="w-10 h-10 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="text-xs font-medium">Belum ada pendonor yang terkonfirmasi</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Section -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-xs font-bold text-gray-800 uppercase tracking-widest">Prediksi Jangkauan</h3>
                    <button @click="loadPreview()" class="p-1 text-gray-400 hover:text-brand-600 hover:bg-brand-50 rounded transition-colors" :disabled="isLoadingPreview">
                        <svg x-show="!isLoadingPreview" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <svg x-show="isLoadingPreview" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </button>
                </div>
                <div class="p-4 bg-gray-50/50 space-y-3 max-h-[500px] overflow-y-auto">
                    <template x-for="p in previewDonors" :key="p.id">
                        <div class="p-3 bg-white rounded-md border border-gray-100 shadow-sm">
                            <div class="flex justify-between items-start">
                                <span class="text-xs font-bold text-gray-800" x-text="p.name"></span>
                                <span class="text-[10px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded" x-text="parseFloat(p.distance_km).toFixed(2) + ' KM'"></span>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1 uppercase" x-text="'Gol ' + p.blood_type + ' • ' + p.phone"></p>
                        </div>
                    </template>
                    <div x-show="previewDonors.length === 0" class="py-12 text-center opacity-30">
                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        <p class="text-[10px] font-bold uppercase tracking-widest">Klik Refresh untuk pindai area</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('bloodRequestManager', (requestId) => ({
        openWaModal: false,
        isBlasting: false,
        activeCandidates: [],
        previewDonors: [],
        isLoadingPreview: false,
        status: '{{ $bloodRequest->status }}',
        intervalId: null,

        init() {
            this.fetchActiveCandidates();
            this.fetchStatus();
            this.intervalId = setInterval(() => {
                this.fetchActiveCandidates();
                this.fetchStatus();
            }, 30000);
        },

        async fetchActiveCandidates() {
            try {
                const res = await fetch(`/api/admin-poll/blood-requests/${requestId}/candidates`);
                if(res.ok) this.activeCandidates = await res.json();
            } catch(e) {}
        },

        async fetchStatus() {
            try {
                const res = await fetch(`/api/admin-poll/blood-requests/${requestId}/status`);
                if(res.ok) this.status = (await res.json()).status;
            } catch(e) {}
        },

        async loadPreview() {
            this.isLoadingPreview = true;
            try {
                const res = await fetch(`/api/admin-poll/blood-requests/${requestId}/preview`);
                if(res.ok) this.previewDonors = await res.json();
            } catch(e) {}
            this.isLoadingPreview = false;
        }
    }));
});
</script>
@endpush
@endsection
