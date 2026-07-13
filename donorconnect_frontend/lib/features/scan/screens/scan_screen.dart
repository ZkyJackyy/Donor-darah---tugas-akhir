import 'package:flutter/material.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../../../core/constants/app_colors.dart';

class ScanScreen extends StatefulWidget {
  const ScanScreen({super.key});

  @override
  State<ScanScreen> createState() => _ScanScreenState();
}

class _ScanScreenState extends State<ScanScreen> {
  MobileScannerController? _controller;
  bool _isProcessing = false;
  bool _hasError = false;
  final _kodeController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _initCamera();
  }

  @override
  void dispose() {
    _controller?.dispose();
    _kodeController.dispose();
    super.dispose();
  }

  Future<void> _initCamera() async {
    try {
      _controller = MobileScannerController(
        detectionSpeed: DetectionSpeed.normal,
        facing: CameraFacing.back,
      );
      await _controller!.start();
    } catch (e) {
      if (mounted) {
        setState(() {
          _hasError = true;
        });
      }
    }
  }

  void _onDetect(BarcodeCapture capture) {
    if (_isProcessing) return;

    final List<Barcode> barcodes = capture.barcodes;
    if (barcodes.isEmpty) return;

    final String? rawValue = barcodes.first.rawValue;
    if (rawValue == null || rawValue.isEmpty) return;

    setState(() => _isProcessing = true);

    Navigator.of(context).pop(rawValue);
  }

  void _submitKodeManual() {
    final kode = _kodeController.text.trim();
    if (kode.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Masukkan kode verifikasi')),
      );
      return;
    }
    Navigator.of(context).pop(kode);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        title: const Text('Verifikasi Donor', style: TextStyle(color: Colors.white)),
        backgroundColor: Colors.black,
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          if (!_hasError)
            IconButton(
              icon: const Icon(Icons.flash_on, color: Colors.white),
              onPressed: () => _controller?.toggleTorch(),
            ),
        ],
      ),
      body: SingleChildScrollView(
        child: Column(
          children: [
            if (_hasError)
              _buildErrorView()
            else
              _buildScannerView(),
            const SizedBox(height: 16),
            _buildManualEntry(),
          ],
        ),
      ),
    );
  }

  Widget _buildErrorView() {
    return Container(
      height: 300,
      child: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.camera_alt_outlined, color: Colors.white54, size: 80),
              const SizedBox(height: 16),
              const Text(
                'Kamera tidak dapat diakses',
                style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              Text(
                'Pastikan izin kamera sudah diberikan di pengaturan aplikasi.',
                textAlign: TextAlign.center,
                style: TextStyle(color: Colors.white.withOpacity(0.7), fontSize: 14),
              ),
              const SizedBox(height: 24),
              ElevatedButton.icon(
                onPressed: () async {
                  setState(() {
                    _hasError = false;
                  });
                  await _initCamera();
                },
                icon: const Icon(Icons.refresh),
                label: const Text('Coba Lagi'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  foregroundColor: Colors.white,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildScannerView() {
    return SizedBox(
      height: 350,
      child: Stack(
        children: [
          if (_controller != null)
            MobileScanner(
              controller: _controller!,
              onDetect: _onDetect,
            ),
          CustomPaint(
            size: const Size(double.infinity, 350),
            painter: _ScannerOverlayPainter(),
          ),
          Positioned(
            bottom: 20,
            left: 0,
            right: 0,
            child: Center(
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                decoration: BoxDecoration(
                  color: Colors.black54,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Text(
                  'Arahkan kamera ke QR Code',
                  style: TextStyle(color: Colors.white, fontSize: 14),
                ),
              ),
            ),
          ),
          if (_isProcessing)
            const Center(
              child: CircularProgressIndicator(color: Colors.white),
            ),
        ],
      ),
    );
  }

  Widget _buildManualEntry() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 24),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(6),
                decoration: const BoxDecoration(
                  color: AppColors.primary,
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.keyboard, color: Colors.white, size: 16),
              ),
              const SizedBox(width: 10),
              const Text(
                'Atau masukkan kode verifikasi',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: AppColors.primaryDark,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          TextField(
            controller: _kodeController,
            decoration: InputDecoration(
              hintText: 'Contoh: DNR-A7K3M2',
              hintStyle: TextStyle(color: Colors.grey.shade400),
              prefixIcon: const Icon(Icons.vpn_key, color: AppColors.primary),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide(color: Colors.grey.shade300),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide(color: Colors.grey.shade300),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(color: AppColors.primary, width: 2),
              ),
              filled: true,
              fillColor: Colors.grey.shade50,
            ),
            style: const TextStyle(
              fontSize: 18,
              letterSpacing: 3,
              fontWeight: FontWeight.bold,
            ),
            textCapitalization: TextCapitalization.characters,
          ),
          const SizedBox(height: 12),
          ElevatedButton(
            onPressed: _submitKodeManual,
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            child: const Text(
              'Verifikasi Kode',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );
  }
}

class _ScannerOverlayPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final double scanAreaSize = size.width * 0.7;
    final double left = (size.width - scanAreaSize) / 2;
    final double top = (size.height - scanAreaSize) / 2 - 40;

    final Paint paint = Paint()..color = Colors.black.withOpacity(0.5);
    canvas.drawPath(
      Path.combine(
        PathOperation.difference,
        Path()..addRect(Rect.fromLTWH(0, 0, size.width, size.height)),
        Path()
          ..addRRect(RRect.fromRectAndRadius(
            Rect.fromLTWH(left, top, scanAreaSize, scanAreaSize),
            const Radius.circular(16),
          )),
      ),
      paint,
    );

    final Paint borderPaint = Paint()
      ..color = AppColors.primary
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3;
    canvas.drawRRect(
      RRect.fromRectAndRadius(
        Rect.fromLTWH(left, top, scanAreaSize, scanAreaSize),
        const Radius.circular(16),
      ),
      borderPaint,
    );

    final double cornerSize = 30;
    final Paint cornerPaint = Paint()
      ..color = AppColors.primary
      ..style = PaintingStyle.stroke
      ..strokeWidth = 4
      ..strokeCap = StrokeCap.round;

    canvas.drawLine(Offset(left, top + cornerSize), Offset(left, top), cornerPaint);
    canvas.drawLine(Offset(left, top), Offset(left + cornerSize, top), cornerPaint);
    canvas.drawLine(Offset(left + scanAreaSize - cornerSize, top), Offset(left + scanAreaSize, top), cornerPaint);
    canvas.drawLine(Offset(left + scanAreaSize, top), Offset(left + scanAreaSize, top + cornerSize), cornerPaint);
    canvas.drawLine(Offset(left, top + scanAreaSize - cornerSize), Offset(left, top + scanAreaSize), cornerPaint);
    canvas.drawLine(Offset(left, top + scanAreaSize), Offset(left + cornerSize, top + scanAreaSize), cornerPaint);
    canvas.drawLine(Offset(left + scanAreaSize - cornerSize, top + scanAreaSize), Offset(left + scanAreaSize, top + scanAreaSize), cornerPaint);
    canvas.drawLine(Offset(left + scanAreaSize, top + scanAreaSize - cornerSize), Offset(left + scanAreaSize, top + scanAreaSize), cornerPaint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
