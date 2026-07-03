import { SetMetadata } from '@nestjs/common';

export const RAW_RESPONSE_KEY = 'rawResponse';

/** Omite el wrapper { ok, data } para descargas de archivos. */
export const RawResponse = () => SetMetadata(RAW_RESPONSE_KEY, true);
