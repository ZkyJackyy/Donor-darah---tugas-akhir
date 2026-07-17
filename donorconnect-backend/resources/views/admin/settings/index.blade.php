@extends('layouts.admin')

@section('page_title', 'Pengaturan')

@section('content')
<div class="space-y-6 max-w-3xl">

    <div>
        <h1 class="font-bold text-2xl text-gray-900 tracking-tight">Pengaturan Sistem</h1>
        <p class="text-sm text-gray-400 mt-0.5">Konfigurasi integrasi dan informasi sistem</p>
    </div>

    <!-- Fonnte API Config -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-bold text-gray-900">WhatsApp (Fonnte API)</h3>
            <p class="text-xs text-gray-400 mt-0.5">Integrasi untuk mengirim notifikasi via WhatsApp</p>
        </div>
        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-700">Status Koneksi</p>
                    <p class="text-xs text-gray-400 mt-0.5">Pastikan FONNTE_API_KEY sudah diisi di .env</p>
                </div>
                @if($fonnteConfigured)
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Terkonfigurasi
                </span>
                @else
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 bg-brand-50 px-3 py-1.5 rounded-lg border border-brand-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-brand-500"></span>
                    Belum Dikonfigurasi
                </span>
                @endif
            </div>
            <div x-data="{ testing: false, result: null }">
                <button @click="testing = true; result = null; fetch('/admin/settings/test-fonnte', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } }).then(r => r.json()).then(d => { result = d; testing = false; })" :disabled="testing" class="bg-white border border-gray-200 text-gray-600 rounded-xl px-4 py-2.5 text-sm font-semibold hover:bg-gray-50 transition-colors flex items-center gap-2" :class="{ 'opacity-50 cursor-not-allowed': testing }">
                    <svg x-show="!testing" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    <svg x-show="testing" class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span x-text="testing ? 'Menguji...' : 'Tes Koneksi'"></span>
                </button>
                <div x-show="result" x-cloak class="mt-3 p-3 rounded-xl text-xs font-medium" :class="result?.success ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-brand-50 text-brand-700 border border-brand-200'">
                    <span x-text="result?.message"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Wave Radius Config -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-bold text-gray-900">Radius Gelombang Broadcast</h3>
            <p class="text-xs text-gray-400 mt-0.5">Jarak maksimum pencarian pendonor per gelombang</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach($waveRanges as $wave => $range)
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Gelombang {{ $wave }}</div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-xl font-bold text-gray-900">{{ $range['min'] }}–{{ $range['max'] }}</span>
                        <span class="text-xs text-gray-400 font-medium">km</span>
                    </div>
                </div>
                @endforeach
            </div>
            <p class="text-xs text-gray-400 mt-4">Konfigurasi radius diatur melalui file .env (<code class="bg-gray-100 px-1.5 py-0.5 rounded-lg text-gray-500 font-mono text-[10px]">DONORCONNECT_WAVE_1_KM</code>, dst.)</p>
        </div>
    </div>

    <!-- Default Hospital -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-bold text-gray-900">Lokasi Default</h3>
            <p class="text-xs text-gray-400 mt-0.5">Titik awal saat membuat permintaan darah baru</p>
        </div>
        <div class="p-6 space-y-3">
            <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                <span class="text-sm text-gray-500">Nama</span>
                <span class="text-sm font-semibold text-gray-900">{{ $defaultHospital['name'] }}</span>
            </div>
            <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                <span class="text-sm text-gray-500">Alamat</span>
                <span class="text-sm font-semibold text-gray-900">{{ $defaultHospital['address'] }}</span>
            </div>
            <div class="flex items-center justify-between py-2.5">
                <span class="text-sm text-gray-500">Koordinat</span>
                <span class="text-sm font-semibold text-gray-900 font-mono">{{ $defaultHospital['lat'] }}, {{ $defaultHospital['lng'] }}</span>
            </div>
            <p class="text-xs text-gray-400 mt-3">Diatur melalui .env (<code class="bg-gray-100 px-1.5 py-0.5 rounded-lg text-gray-500 font-mono text-[10px]">DONORCONNECT_DEFAULT_LAT</code>, <code class="bg-gray-100 px-1.5 py-0.5 rounded-lg text-gray-500 font-mono text-[10px]">DONORCONNECT_DEFAULT_LNG</code>)</p>
        </div>
    </div>

    <!-- System Info -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-bold text-gray-900">Informasi Sistem</h3>
            <p class="text-xs text-gray-400 mt-0.5">Detail versi dan konfigurasi backend</p>
        </div>
        <div class="p-6 space-y-3">
            <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                <span class="text-sm text-gray-500">Laravel Version</span>
                <span class="text-sm font-semibold text-gray-900 font-mono">{{ app()->version() }}</span>
            </div>
            <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                <span class="text-sm text-gray-500">PHP Version</span>
                <span class="text-sm font-semibold text-gray-900 font-mono">{{ phpversion() }}</span>
            </div>
            <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                <span class="text-sm text-gray-500">Queue Driver</span>
                <span class="text-sm font-semibold text-gray-900 font-mono">{{ config('queue.default') }}</span>
            </div>
            <div class="flex items-center justify-between py-2.5">
                <span class="text-sm text-gray-500">Donor Interval</span>
                <span class="text-sm font-semibold text-gray-900">{{ $cooldownDays }} hari (WHO)</span>
            </div>
        </div>
    </div>
</div>
@endsection
