import { MemoCategory } from './types';

export interface CategoryConfig {
  id: MemoCategory;
  label: string;
  shortLabel: string;
  emoji: string;
  /** Ionicons name */
  icon: string;
  /** Accent / foreground color */
  color: string;
  /** Light background tint */
  bgColor: string;
  /** Description shown in picker */
  description: string;
  /** Placeholder for the title field */
  titlePlaceholder: string;
  /** Pre-filled content template */
  contentTemplate: string;
  /** Section label shown in edit screen header */
  editLabel: string;
}

export const CATEGORIES: Record<MemoCategory, CategoryConfig> = {
  test: {
    id: 'test',
    label: 'テスト情報',
    shortLabel: 'テスト',
    emoji: '📝',
    icon: 'document-text-outline',
    color: '#EF4444',
    bgColor: '#FEF2F2',
    description: '試験日・出題範囲・ポイントをまとめて共有',
    titlePlaceholder: '教科名を入力 (例: 数学II)',
    contentTemplate:
      '試験日: \n出題範囲: \n重要ポイント: \n持ち物: ',
    editLabel: 'テスト情報',
  },
  homework: {
    id: 'homework',
    label: '宿題・課題',
    shortLabel: '宿題',
    emoji: '📚',
    icon: 'book-outline',
    color: '#10B981',
    bgColor: '#F0FDF4',
    description: '提出日・課題内容を記録してすぐに共有',
    titlePlaceholder: '教科名を入力 (例: 英語)',
    contentTemplate:
      '提出日: \n内容: \nメモ: ',
    editLabel: '宿題・課題',
  },
  note: {
    id: 'note',
    label: '自由メモ',
    shortLabel: 'メモ',
    emoji: '📓',
    icon: 'create-outline',
    color: '#8B5CF6',
    bgColor: '#F5F3FF',
    description: '連絡事項・部活・なんでも自由に',
    titlePlaceholder: 'タイトルを入力',
    contentTemplate: '',
    editLabel: '自由メモ',
  },
};

export const CATEGORY_ORDER: MemoCategory[] = ['test', 'homework', 'note'];

export function getCategoryConfig(category?: MemoCategory): CategoryConfig {
  return CATEGORIES[category ?? 'note'];
}
