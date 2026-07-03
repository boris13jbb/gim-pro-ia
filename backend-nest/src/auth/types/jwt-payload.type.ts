import { AppRole } from '../../common/constants/roles.constant';

export type UserType = 'staff' | 'member';

export interface JwtPayload {
  sub: number;
  email: string;
  role: AppRole;
  userType: UserType;
  memberId?: number;
  jti?: string;
  type?: 'access' | 'refresh';
}
