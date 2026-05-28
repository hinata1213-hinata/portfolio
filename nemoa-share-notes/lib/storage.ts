import AsyncStorage from '@react-native-async-storage/async-storage';
import { Memo } from './types';

const STORAGE_KEY = '@qrmemo:memos';

export async function getAllMemos(): Promise<Memo[]> {
  try {
    const json = await AsyncStorage.getItem(STORAGE_KEY);
    if (!json) return [];
    return JSON.parse(json) as Memo[];
  } catch {
    return [];
  }
}

export async function saveMemo(memo: Memo): Promise<void> {
  const memos = await getAllMemos();
  const idx = memos.findIndex((m) => m.id === memo.id);
  if (idx >= 0) {
    memos[idx] = memo;
  } else {
    memos.unshift(memo);
  }
  await AsyncStorage.setItem(STORAGE_KEY, JSON.stringify(memos));
}

export async function deleteMemo(id: string): Promise<void> {
  const memos = await getAllMemos();
  const filtered = memos.filter((m) => m.id !== id);
  await AsyncStorage.setItem(STORAGE_KEY, JSON.stringify(filtered));
}

export async function getMemoById(id: string): Promise<Memo | null> {
  const memos = await getAllMemos();
  return memos.find((m) => m.id === id) ?? null;
}
