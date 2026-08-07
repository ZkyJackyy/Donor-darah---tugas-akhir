import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import '../../../core/constants/app_colors.dart';
import '../../../shared/widgets/custom_button.dart';

class PilihLokasiScreen extends StatefulWidget {
  final double? initialLatitude;
  final double? initialLongitude;

  const PilihLokasiScreen({super.key, this.initialLatitude, this.initialLongitude});

  @override
  State<PilihLokasiScreen> createState() => _PilihLokasiScreenState();
}

class _PilihLokasiScreenState extends State<PilihLokasiScreen> {
  // Default ke UDD PMI Kota Padang kalau belum ada lokasi sebelumnya.
  static const _defaultLatLng = LatLng(-0.944554954176654, 100.3679109288369);

  late LatLng _picked;

  @override
  void initState() {
    super.initState();
    _picked = (widget.initialLatitude != null && widget.initialLongitude != null)
        ? LatLng(widget.initialLatitude!, widget.initialLongitude!)
        : _defaultLatLng;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Pilih Lokasi Rumah Sakit', style: TextStyle(color: Colors.white)),
        backgroundColor: AppColors.primary,
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: Stack(
        children: [
          GoogleMap(
            initialCameraPosition: CameraPosition(target: _picked, zoom: 15),
            onTap: (latLng) => setState(() => _picked = latLng),
            markers: {
              Marker(markerId: const MarkerId('picked'), position: _picked),
            },
            myLocationButtonEnabled: false,
            zoomControlsEnabled: false,
          ),
          Positioned(
            left: 16,
            right: 16,
            bottom: 24,
            child: Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(color: Colors.black.withValues(alpha: 0.1), blurRadius: 10, offset: const Offset(0, 4)),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Text(
                    'Ketuk peta untuk menandai lokasi rumah sakit',
                    style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
                  ),
                  const SizedBox(height: 12),
                  CustomButton(
                    text: 'Gunakan Lokasi Ini',
                    onPressed: () => Navigator.pop(context, _picked),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
