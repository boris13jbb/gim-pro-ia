import { AppRole } from '../../common/constants/roles.constant';

export interface AuthUserResponse {
  id: number;

  nombre: string;

  email: string;

  rol: AppRole;

  estado: string;
}

export interface MemberAuthUserResponse {
  id: number;

  nombre: string;

  dni: string;

  email: string | null;

  telefono: string | null;

  estado: string;

  foto: string | null;

  photoUrl: string | null;

  fechaRegistro: Date | null;
}

export interface TokenPairResponse {
  accessToken: string;

  refreshToken: string;

  user: AuthUserResponse | MemberAuthUserResponse;
}
