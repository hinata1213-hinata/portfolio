export type MemoCategory = 'test' | 'homework' | 'note';

export interface Memo {
  id: string;
  title: string;
  content: string;
  category: MemoCategory;
  createdAt: number;
  updatedAt: number;
}

export interface QRData {
  t: string;          // title
  c: string;          // content
  k: MemoCategory;    // category (kind)
  u: number;          // updatedAt timestamp
}
