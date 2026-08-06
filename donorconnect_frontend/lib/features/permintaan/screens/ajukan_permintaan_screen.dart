import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import '../../../core/constants/app_colors.dart';
import '../../../shared/widgets/app_snackbar.dart';
import '../../../shared/widgets/custom_button.dart';
import '../providers/permintaan_provider.dart';

class AjukanPermintaanScreen extends StatefulWidget {
  const AjukanPermintaanScreen({super.key});

  @override
  State<AjukanPermintaanScreen> createState() => _AjukanPermintaanScreenState();
}

class _AjukanPermintaanScreenState extends State<AjukanPermintaanScreen> {
  final _formKey = GlobalKey<FormState>();
  final _requiredBagsController = TextEditingController(text: '1');
  final _patientNameController = TextEditingController();
  final _hospitalNameController = TextEditingController();
  final _hospitalAddressController = TextEditingController();
  final _notesController = TextEditingController();

  String? _bloodType;
  String? _rhesus;
  String? _patientRelationship;
  String _urgencyLevel = 'normal';
  DateTime? _deadline;

  static const _bloodTypes = ['A', 'B', 'AB', 'O'];
  static const _rhesusOptions = ['+', '-'];
  static const _relationshipOptions = [
    'Anak',
    'Orang Tua',
    'Pasangan',
    'Saudara Kandung',
    'Lainnya',
  ];
  static const _urgencyOptions = [
    ('normal', 'Normal'),
    ('urgent', 'Penting'),
    ('critical', 'Darurat'),
  ];

  @override
  void dispose() {
    _requiredBagsController.dispose();
    _patientNameController.dispose();
    _hospitalNameController.dispose();
    _hospitalAddressController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _selectDeadline(BuildContext context) async {
    final now = DateTime.now();
    final pickedDate = await showDatePicker(
      context: context,
      initialDate: _deadline ?? now.add(const Duration(days: 1)),
      firstDate: now,
      lastDate: now.add(const Duration(days: 365)),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(primary: AppColors.primary),
          ),
          child: child!,
        );
      },
    );
    if (pickedDate == null || !context.mounted) return;

    final pickedTime = await showTimePicker(
      context: context,
      initialTime: _deadline != null
          ? TimeOfDay.fromDateTime(_deadline!)
          : const TimeOfDay(hour: 17, minute: 0),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(primary: AppColors.primary),
          ),
          child: child!,
        );
      },
    );
    if (pickedTime == null) return;

    setState(() {
      _deadline = DateTime(
        pickedDate.year,
        pickedDate.month,
        pickedDate.day,
        pickedTime.hour,
        pickedTime.minute,
      );
    });
  }

  void _submit() async {
    if (!_formKey.currentState!.validate()) return;

    if (_bloodType == null) {
      AppSnackbar.showError(context, 'Golongan darah wajib dipilih');
      return;
    }
    if (_rhesus == null) {
      AppSnackbar.showError(context, 'Rhesus wajib dipilih');
      return;
    }
    if (_patientRelationship == null) {
      AppSnackbar.showError(context, 'Hubungan dengan pasien wajib dipilih');
      return;
    }
    if (_deadline == null) {
      AppSnackbar.showError(context, 'Batas waktu wajib diisi');
      return;
    }

    final notes = _notesController.text.trim();

    final success = await context.read<PermintaanProvider>().submitPermintaan(
          bloodType: _bloodType!,
          rhesus: _rhesus!,
          requiredBags: int.parse(_requiredBagsController.text),
          patientName: _patientNameController.text.trim(),
          patientRelationship: _patientRelationship!,
          hospitalName: _hospitalNameController.text.trim(),
          hospitalAddress: _hospitalAddressController.text.trim(),
          urgencyLevel: _urgencyLevel,
          deadline: DateFormat('yyyy-MM-dd HH:mm:ss').format(_deadline!),
          notes: notes.isNotEmpty ? notes : null,
        );

    if (!mounted) return;

    if (success) {
      AppSnackbar.showSuccess(context, 'Pengajuan berhasil dikirim, menunggu persetujuan admin');
      context.pop();
    } else {
      AppSnackbar.showError(
        context,
        context.read<PermintaanProvider>().error ?? 'Gagal mengirim pengajuan',
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSubmitting = context.watch<PermintaanProvider>().isSubmitting;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Ajukan Permintaan Darah',
            style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        backgroundColor: AppColors.primary,
        automaticallyImplyLeading: true,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24.0),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.primaryLight.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: AppColors.primaryLight),
                ),
                child: const Row(
                  children: [
                    Icon(Icons.info_outline, color: AppColors.primary),
                    SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        'Pengajuan Anda akan ditinjau admin PMI sebelum pencarian pendonor dimulai.',
                        style: TextStyle(fontSize: 13, color: AppColors.textPrimary),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),
              const Text('Informasi Pasien',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textSecondary)),
              const SizedBox(height: 12),
              TextFormField(
                controller: _patientNameController,
                decoration: const InputDecoration(
                  labelText: 'Nama Pasien',
                  prefixIcon: Icon(Icons.person_outline),
                  border: OutlineInputBorder(),
                ),
                validator: (val) => val == null || val.trim().isEmpty ? 'Nama pasien tidak boleh kosong' : null,
              ),
              const SizedBox(height: 16),
              DropdownButtonFormField<String>(
                value: _patientRelationship,
                decoration: const InputDecoration(
                  labelText: 'Hubungan dengan Pasien',
                  prefixIcon: Icon(Icons.family_restroom_outlined),
                  border: OutlineInputBorder(),
                ),
                items: _relationshipOptions
                    .map((r) => DropdownMenuItem(value: r, child: Text(r)))
                    .toList(),
                onChanged: (val) => setState(() => _patientRelationship = val),
                validator: (val) => val == null ? 'Wajib dipilih' : null,
              ),
              const SizedBox(height: 24),
              const Text('Kebutuhan Darah',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textSecondary)),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: DropdownButtonFormField<String>(
                      value: _bloodType,
                      decoration: const InputDecoration(
                        labelText: 'Golongan Darah',
                        border: OutlineInputBorder(),
                      ),
                      items: _bloodTypes
                          .map((b) => DropdownMenuItem(value: b, child: Text(b)))
                          .toList(),
                      onChanged: (val) => setState(() => _bloodType = val),
                      validator: (val) => val == null ? 'Wajib dipilih' : null,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: DropdownButtonFormField<String>(
                      value: _rhesus,
                      decoration: const InputDecoration(
                        labelText: 'Rhesus',
                        border: OutlineInputBorder(),
                      ),
                      items: _rhesusOptions
                          .map((r) => DropdownMenuItem(value: r, child: Text(r)))
                          .toList(),
                      onChanged: (val) => setState(() => _rhesus = val),
                      validator: (val) => val == null ? 'Wajib dipilih' : null,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _requiredBagsController,
                decoration: const InputDecoration(
                  labelText: 'Jumlah Kantong Dibutuhkan',
                  prefixIcon: Icon(Icons.bloodtype_outlined),
                  border: OutlineInputBorder(),
                ),
                keyboardType: TextInputType.number,
                validator: (val) {
                  if (val == null || val.trim().isEmpty) return 'Wajib diisi';
                  final n = int.tryParse(val);
                  if (n == null || n < 1) return 'Masukkan angka minimal 1';
                  return null;
                },
              ),
              const SizedBox(height: 16),
              DropdownButtonFormField<String>(
                value: _urgencyLevel,
                decoration: const InputDecoration(
                  labelText: 'Tingkat Urgensi',
                  prefixIcon: Icon(Icons.priority_high_outlined),
                  border: OutlineInputBorder(),
                ),
                items: _urgencyOptions
                    .map((u) => DropdownMenuItem(value: u.$1, child: Text(u.$2)))
                    .toList(),
                onChanged: (val) => setState(() => _urgencyLevel = val ?? 'normal'),
              ),
              const SizedBox(height: 16),
              InkWell(
                onTap: () => _selectDeadline(context),
                child: InputDecorator(
                  decoration: const InputDecoration(
                    labelText: 'Batas Waktu Dibutuhkan',
                    prefixIcon: Icon(Icons.event_outlined),
                    border: OutlineInputBorder(),
                  ),
                  child: Text(
                    _deadline == null
                        ? 'Pilih tanggal & jam'
                        : DateFormat('dd MMMM yyyy, HH:mm').format(_deadline!),
                  ),
                ),
              ),
              const SizedBox(height: 24),
              const Text('Rumah Sakit Tujuan',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textSecondary)),
              const SizedBox(height: 12),
              TextFormField(
                controller: _hospitalNameController,
                decoration: const InputDecoration(
                  labelText: 'Nama Rumah Sakit',
                  prefixIcon: Icon(Icons.local_hospital_outlined),
                  border: OutlineInputBorder(),
                  hintText: 'Kosongkan jika mengikuti lokasi PMI',
                ),
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _hospitalAddressController,
                decoration: const InputDecoration(
                  labelText: 'Alamat Rumah Sakit',
                  prefixIcon: Icon(Icons.location_on_outlined),
                  border: OutlineInputBorder(),
                ),
                maxLines: 2,
              ),
              const SizedBox(height: 24),
              const Text('Catatan Tambahan',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textSecondary)),
              const SizedBox(height: 12),
              TextFormField(
                controller: _notesController,
                decoration: const InputDecoration(
                  labelText: 'Catatan (opsional)',
                  prefixIcon: Icon(Icons.notes_outlined),
                  border: OutlineInputBorder(),
                ),
                maxLines: 3,
              ),
              const SizedBox(height: 40),
              CustomButton(
                text: 'Kirim Pengajuan',
                onPressed: _submit,
                isLoading: isSubmitting,
              ),
              const SizedBox(height: 32),
            ],
          ),
        ),
      ),
    );
  }
}
