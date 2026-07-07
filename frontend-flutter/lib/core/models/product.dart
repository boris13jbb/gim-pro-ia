class Product {
  Product({
    required this.id,
    required this.name,
    required this.salePrice,
    required this.stock,
    this.code,
    this.categoryName,
    this.imageUrl,
    required this.status,
  });

  final int id;
  final String name;
  final double salePrice;
  final int stock;
  final String? code;
  final String? categoryName;
  final String? imageUrl;
  final String status;

  bool get isActive => status == 'activo';
  bool get hasStock => stock > 0;

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: json['id'] as int,
      name: json['name']?.toString() ?? json['nombre']?.toString() ?? '',
      salePrice: _parsePrice(json['salePrice'] ?? json['precio_venta']),
      stock: json['stock'] as int? ?? 0,
      code: json['code']?.toString() ?? json['codigo']?.toString(),
      categoryName:
          json['categoryName']?.toString() ?? json['category']?.toString(),
      imageUrl: json['imageUrl']?.toString(),
      status: json['status']?.toString() ?? json['estado']?.toString() ?? 'activo',
    );
  }
}

double _parsePrice(dynamic value) {
  if (value == null) return 0;
  if (value is num) return value.toDouble();
  return double.tryParse(value.toString()) ?? 0;
}
