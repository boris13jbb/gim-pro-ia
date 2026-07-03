import { categorias, productos, productos_estado } from '@prisma/client';
import { buildProductPhotoUrl } from '../config/upload.config';

export type ProductWithCategory = productos & {
  categorias?: Pick<categorias, 'id' | 'nombre' | 'estado'> | null;
};

export function mapProduct(row: ProductWithCategory) {
  return {
    id: row.id,
    categoryId: row.categoria_id,
    categoryName: row.categorias?.nombre ?? null,
    categoryStatus: row.categorias?.estado ?? null,
    code: row.codigo,
    name: row.nombre,
    purchasePrice: Number(row.precio_compra),
    salePrice: Number(row.precio_venta),
    stock: row.stock ?? 0,
    imageUrl: buildProductPhotoUrl(row.foto),
    status: row.estado ?? productos_estado.activo,
  };
}
