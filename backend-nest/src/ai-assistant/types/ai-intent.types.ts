/** Intenciones del socio en el chat del gimnasio (Iron Gym). */
export type AiMemberIntent =
  | 'GREETING'
  | 'MEMBERSHIP'
  | 'ATTENDANCE'
  | 'WORKOUT'
  | 'BODY_PROGRESS'
  | 'GENERAL_FITNESS'
  | 'APP_HELP'
  | 'OTHER';

export type AiIntentResult = {
  intent: AiMemberIntent;
  /** Tema breve detectado (opcional), ej. "vencimiento de plan". */
  topic?: string;
};

export type AiIntentHistoryMessage = {
  role: 'user' | 'assistant';
  content: string;
};
