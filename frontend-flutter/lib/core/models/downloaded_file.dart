/// Archivo binario descargado desde la API (exportaciones Excel/PDF).
class DownloadedFile {
  const DownloadedFile({
    required this.bytes,
    required this.filename,
    required this.mimeType,
  });

  final List<int> bytes;
  final String filename;
  final String mimeType;
}
