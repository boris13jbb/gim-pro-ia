import { join } from 'node:path';

const ALLOWED_IMAGE_EXTENSIONS = new Set(['jpg', 'jpeg', 'png', 'webp']);

export function getUploadConfig() {
  const root = process.env.UPLOADS_DIR ?? join(process.cwd(), 'uploads', 'members');
  const maxSizeMb = Number(process.env.MAX_PHOTO_SIZE_MB ?? 5);
  return {
    membersDir: root,
    maxFileSizeBytes: maxSizeMb * 1024 * 1024,
    allowedMimeTypes: [
      'image/jpeg',
      'image/png',
      'image/webp',
    ] as const,
    allowedExtensions: ALLOWED_IMAGE_EXTENSIONS,
    publicPath: '/api/uploads/members',
  };
}

export function buildMemberPhotoUrl(filename: string | null | undefined): string | null {
  if (!filename) return null;
  return `${getUploadConfig().publicPath}/${filename}`;
}

export function getProductUploadConfig() {
  const root =
    process.env.PRODUCT_UPLOADS_DIR ?? join(process.cwd(), 'uploads', 'products');
  const maxSizeMb = Number(process.env.MAX_PHOTO_SIZE_MB ?? 5);
  return {
    productsDir: root,
    maxFileSizeBytes: maxSizeMb * 1024 * 1024,
    allowedMimeTypes: [
      'image/jpeg',
      'image/png',
      'image/webp',
    ] as const,
    allowedExtensions: ALLOWED_IMAGE_EXTENSIONS,
    publicPath: '/api/uploads/products',
  };
}

export function buildProductPhotoUrl(filename: string | null | undefined): string | null {
  if (!filename) return null;
  return `${getProductUploadConfig().publicPath}/${filename}`;
}
