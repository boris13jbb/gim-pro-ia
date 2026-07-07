export type AiHistoryMessage = {
  role: 'user' | 'model';
  text: string;
};

export type AiReplyParams = {
  memberContext: string;
  history: AiHistoryMessage[];
  message: string;
};
