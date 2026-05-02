import 'dart:async';
import 'package:app_links/app_links.dart';
import 'package:flutter/foundation.dart';

class DeepLinkService {
  late AppLinks _appLinks;
  StreamSubscription<Uri>? _linkSubscription;
  final Function(Uri) onDeepLinkReceived;

  DeepLinkService({required this.onDeepLinkReceived}) {
    _initDeepLinks();
  }

  Future<void> _initDeepLinks() async {
    _appLinks = AppLinks();

    // Check initial link if app was in cold state (terminated)
    try {
      final initialLink = await _appLinks.getInitialLink();
      if (initialLink != null) {
        onDeepLinkReceived(initialLink);
      }
    } catch (e) {
      debugPrint("Error fetching initial link: $e");
    }

    // Handle link when app is in warm state (foreground or background)
    _linkSubscription = _appLinks.uriLinkStream.listen((uri) {
      onDeepLinkReceived(uri);
    }, onError: (err) {
      debugPrint("Error on deep link stream: $err");
    });
  }

  void dispose() {
    _linkSubscription?.cancel();
  }
}
