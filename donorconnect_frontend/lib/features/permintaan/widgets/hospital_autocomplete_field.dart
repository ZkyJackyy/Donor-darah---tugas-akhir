import 'dart:async';
import 'package:flutter/material.dart';
import '../../../core/constants/app_colors.dart';
import '../../../shared/models/place_prediction.dart';

class HospitalAutocompleteField extends StatefulWidget {
  final TextEditingController controller;
  final String labelText;
  final IconData icon;
  final int maxLines;
  final bool fillAddressOnSelect;
  final String? Function(String?)? validator;
  final Future<List<PlacePrediction>> Function(String query) onSearch;
  final void Function(PlacePrediction prediction) onPlaceSelected;
  // Fired only for genuine user keystrokes (never for the programmatic
  // controller.text assignment a suggestion pick makes) — lets the parent
  // know the field no longer reflects the picked suggestion, e.g. to clear
  // coordinates that were captured for that pick.
  final VoidCallback? onManualEdit;

  const HospitalAutocompleteField({
    super.key,
    required this.controller,
    required this.labelText,
    required this.icon,
    required this.onSearch,
    required this.onPlaceSelected,
    this.maxLines = 1,
    this.fillAddressOnSelect = false,
    this.validator,
    this.onManualEdit,
  });

  @override
  State<HospitalAutocompleteField> createState() => _HospitalAutocompleteFieldState();
}

class _HospitalAutocompleteFieldState extends State<HospitalAutocompleteField> {
  Timer? _debounce;
  List<PlacePrediction> _suggestions = [];
  bool _isSearching = false;

  void _onChanged(String value) {
    widget.onManualEdit?.call();
    _debounce?.cancel();
    if (value.trim().length < 3) {
      setState(() => _suggestions = []);
      return;
    }
    _debounce = Timer(const Duration(milliseconds: 500), () async {
      setState(() => _isSearching = true);
      final results = await widget.onSearch(value);
      if (!mounted) return;
      setState(() {
        _suggestions = results;
        _isSearching = false;
      });
    });
  }

  void _selectSuggestion(PlacePrediction prediction) {
    setState(() => _suggestions = []);

    // Programmatic controller.text assignment does not fire
    // TextFormField.onChanged, so this never re-triggers a search or
    // widget.onManualEdit — no suppression flag needed.
    widget.controller.text = widget.fillAddressOnSelect
        ? prediction.description
        : (prediction.name ?? prediction.description);
    widget.onPlaceSelected(prediction);
  }

  @override
  void dispose() {
    _debounce?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        TextFormField(
          controller: widget.controller,
          decoration: InputDecoration(
            labelText: widget.labelText,
            prefixIcon: Icon(widget.icon),
            border: const OutlineInputBorder(),
            suffixIcon: _isSearching
                ? const Padding(
                    padding: EdgeInsets.all(14),
                    child: SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)),
                  )
                : null,
          ),
          maxLines: widget.maxLines,
          onChanged: _onChanged,
          validator: widget.validator,
        ),
        if (_suggestions.isNotEmpty)
          Container(
            margin: const EdgeInsets.only(top: 4),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: Colors.grey.shade300),
            ),
            constraints: const BoxConstraints(maxHeight: 220),
            child: ListView.separated(
              shrinkWrap: true,
              padding: EdgeInsets.zero,
              itemCount: _suggestions.length,
              separatorBuilder: (_, __) => Divider(height: 1, color: Colors.grey.shade200),
              itemBuilder: (context, index) {
                final s = _suggestions[index];
                return ListTile(
                  dense: true,
                  leading: const Icon(Icons.location_on_outlined, size: 18, color: AppColors.primary),
                  title: Text(s.description, style: const TextStyle(fontSize: 13)),
                  onTap: () => _selectSuggestion(s),
                );
              },
            ),
          ),
      ],
    );
  }
}
