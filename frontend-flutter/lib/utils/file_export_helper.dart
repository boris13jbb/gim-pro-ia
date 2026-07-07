import 'dart:io';
import 'dart:typed_data';

import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';

import '../core/models/downloaded_file.dart';

/// Guarda y comparte archivos exportados (Excel/PDF) en móvil/escritorio.
class FileExportHelper {
  const FileExportHelper._();

  static Future<void> shareDownloadedFile(DownloadedFile file) async {
    final bytes = Uint8List.fromList(file.bytes);

    if (kIsWeb) {
      await SharePlus.instance.share(
        ShareParams(
          files: [
            XFile.fromData(
              bytes,
              name: file.filename,
              mimeType: file.mimeType,
            ),
          ],
          text: 'Reporte financiero',
        ),
      );
      return;
    }

    final directory = await getTemporaryDirectory();
    final path = '${directory.path}/${file.filename}';
    final saved = File(path);
    await saved.writeAsBytes(bytes, flush: true);

    await SharePlus.instance.share(
      ShareParams(
        files: [XFile(path, mimeType: file.mimeType, name: file.filename)],
        text: 'Reporte financiero',
      ),
    );
  }
}
