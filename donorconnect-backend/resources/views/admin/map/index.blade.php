@extends('layouts.admin')

@section('page_title', 'Peta Lokasi')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@section('content')
<div class="space-y-6" x-data="donorMap()">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 animate-fade-in-up">
        <div>
            <h1 class="font-bold text-2xl text-slate-900 tracking-tight">Peta Lokasi Pendonor</h1>
            <p class="text-sm text-slate-400 mt-0.5">Visualisasi distribusi pendonor aktif di sekitar PMI</p>
        </div>
        <div class="flex items-center gap-3">
            <select x-model="filterBloodType" @change="fetchDonors()" class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-600 bg-white focus:outline-none focus:ring-2 focus:ring-crimson-500/20 focus:border-crimson-500 transition-all">
                <option value="">Semua Golongan</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="AB">AB</option>
                <option value="O">O</option>
            </select>
            <select x-model="filterRhesus" @change="fetchDonors()" class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-600 bg-white focus:outline-none focus:ring-2 focus:ring-crimson-500/20 focus:border-crimson-500 transition-all">
                <option value="">Semua Rhesus</option>
                <option value="+">Positif (+)</option>
                <option value="-">Negatif (-)</option>
            </select>
            <div class="text-sm text-slate-400 font-medium">
                <span x-text="donors.length"></span> pendonor
            </div>
        </div>
    </div>

    <!-- Map Container -->
    <div class="animate-fade-in-up delay-1 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div id="map" style="height: 520px; width: 100%;"></div>
    </div>

    <!-- Legend -->
    <div class="animate-fade-in-up delay-2 flex items-center gap-6 text-xs text-slate-500 font-medium">
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-crimson-600 shadow-sm"></span> PMI UDD Padang
        </div>
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-blue-500 shadow-sm"></span> Pendonor Aktif
        </div>
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full border-2 border-blue-400 bg-blue-100"></span> Radius 5 KM
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('donorMap', () => ({
        map: null,
        markers: [],
        circle: null,
        donors: [],
        filterBloodType: '',
        filterRhesus: '',

        init() {
            this.$nextTick(() => {
                this.map = L.map('map').setView([-0.9471, 100.4172], 13);

                L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                    attribution: '© OpenStreetMap contributors © CARTO',
                    maxZoom: 18,
                }).addTo(this.map);

                const pmiIcon = L.divIcon({
                    html: '<div style="background:#B91C1C;width:14px;height:14px;border-radius:50%;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.25)"></div>',
                    iconSize: [14, 14],
                    className: ''
                });
                L.marker([-0.9471, 100.4172], { icon: pmiIcon })
                    .addTo(this.map)
                    .bindPopup('<div style="font-family:DM Sans,sans-serif;padding:4px 0"><b style="font-size:13px">UDD PMI Kota Padang</b><br><span style="font-size:11px;color:#64748b">Jl. Sisingamangarja No.34</span></div>');

                this.circle = L.circle([-0.9471, 100.4172], {
                    radius: 5000,
                    color: '#3B82F6',
                    fillColor: '#3B82F6',
                    fillOpacity: 0.04,
                    weight: 2,
                    dashArray: '8,6'
                }).addTo(this.map);

                this.fetchDonors();
            });
        },

        async fetchDonors() {
            let url = '/admin/map/donors-json?';
            if (this.filterBloodType) url += `blood_type=${this.filterBloodType}&`;
            if (this.filterRhesus) url += `rhesus=${this.filterRhesus}&`;

            try {
                const res = await fetch(url);
                if (res.ok) {
                    this.donors = await res.json();
                    this.renderMarkers();
                }
            } catch(e) {}
        },

        renderMarkers() {
            this.markers.forEach(m => this.map.removeLayer(m));
            this.markers = [];

            const donorIcon = L.divIcon({
                html: '<div style="background:#3B82F6;width:12px;height:12px;border-radius:50%;border:2.5px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.2)"></div>',
                iconSize: [12, 12],
                className: ''
            });

            this.donors.forEach(d => {
                const marker = L.marker([d.latitude, d.longitude], { icon: donorIcon })
                    .addTo(this.map)
                    .bindPopup(`
                        <div style="font-family:DM Sans,sans-serif;padding:4px 0">
                            <b style="font-size:13px">${d.name}</b><br>
                            <span style="font-size:11px;color:#64748b">Gol: ${d.blood_type}${d.rhesus} • ${d.phone || '-'}</span><br>
                            <span style="font-size:11px;color:#94A3B8">${d.last_donor_date ? 'Terakhir donor: ' + d.last_donor_date : 'Belum pernah donor'}</span>
                        </div>
                    `);
                this.markers.push(marker);
            });
        }
    }));
});
</script>
@endpush
@endsection
