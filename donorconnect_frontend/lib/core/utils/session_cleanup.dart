import 'package:flutter/widgets.dart';
import 'package:provider/provider.dart';
import '../../features/permintaan/providers/permintaan_provider.dart';
import '../../features/riwayat/providers/riwayat_provider.dart';
import '../../features/notifikasi/providers/notifikasi_provider.dart';
import '../../features/konfirmasi/providers/konfirmasi_provider.dart';

/// Clears every provider that caches data scoped to the logged-in user.
///
/// The bottom-nav tabs live in a [StatefulShellRoute.indexedStack], so their
/// providers are never disposed across a logout/login cycle — without this,
/// a second user logging in on the same device briefly sees the previous
/// user's permintaan/riwayat/notifikasi list until a manual pull-to-refresh.
/// Call this right after `AuthProvider.logout()` succeeds, before navigating
/// to `/login`, while the screen's [BuildContext] is still valid.
void resetUserScopedProviders(BuildContext context) {
  context.read<PermintaanProvider>().clear();
  context.read<RiwayatProvider>().clear();
  context.read<NotifikasiProvider>().clear();
  context.read<KonfirmasiProvider>().resetTicketData();
}
