import { Memo, MemoCategory, QRData } from './types';

export function encodeQR(memo: Memo): string {
  const data: QRData = {
    t: memo.title,
    c: memo.content,
    k: memo.category,
    u: memo.updatedAt,
  };
  return JSON.stringify(data);
}

export function decodeQR(str: string): QRData | null {
  try {
    const data = JSON.parse(str);
    if (
      typeof data === 'object' &&
      data !== null &&
      typeof data.t === 'string' &&
      typeof data.c === 'string'
    ) {
      // Fallback for QR codes created before category was added
      if (!data.k) data.k = 'note' as MemoCategory;
      return data as QRData;
    }
    return null;
  } catch {
    return null;
  }
}

export function generateId(): string {
  return Date.now().toString(36) + Math.random().toString(36).slice(2);
}

export function formatDate(timestamp: number): string {
  return new Date(timestamp).toLocaleDateString('ja-JP', {
    month: 'short',
    day: 'numeric',
  });
}

export function formatDateTime(timestamp: number): string {
  return new Date(timestamp).toLocaleString('ja-JP', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}
