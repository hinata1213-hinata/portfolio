// Legacy Colors export (for compatibility with any remaining references)
export const Colors = {
  light: {
    text: '#0F172A',
    background: '#F0F4FF',
    tint: '#6366F1',
    icon: '#64748B',
    tabIconDefault: '#94A3B8',
    tabIconSelected: '#6366F1',
  },
  dark: {
    text: '#0F172A',
    background: '#F0F4FF',
    tint: '#6366F1',
    icon: '#64748B',
    tabIconDefault: '#94A3B8',
    tabIconSelected: '#6366F1',
  },
};

// App design tokens
export const AppColors = {
  // Backgrounds
  bg: '#F0F4FF',
  bgCard: '#FFFFFF',
  bgSection: '#F8FAFF',

  // Primary brand (Indigo)
  primary: '#6366F1',
  primaryDark: '#4F46E5',
  primaryLight: '#EEF2FF',
  primaryMid: '#C7D2FE',

  // Text
  textPrimary: '#0F172A',
  textSecondary: '#475569',
  textMuted: '#94A3B8',
  textInverse: '#FFFFFF',

  // Semantic
  danger: '#EF4444',
  dangerLight: '#FEF2F2',
  success: '#10B981',
  successLight: '#D1FAE5',

  // Border / Divider
  border: '#E2E8F0',
  divider: '#F1F5F9',

  // QR code
  qrFg: '#1E1B4B',
  qrBg: '#FFFFFF',

  // ── Category colours ─────────────────────────────────────────────────────
  // テスト (test) — Red
  test: '#EF4444',
  testLight: '#FEF2F2',

  // 時間割 (schedule) — Blue
  schedule: '#3B82F6',
  scheduleLight: '#EFF6FF',

  // 宿題 (homework) — Emerald
  homework: '#10B981',
  homeworkLight: '#F0FDF4',

  // 自由メモ (note) — Violet
  note: '#8B5CF6',
  noteLight: '#F5F3FF',
};

export const Spacing = {
  xs: 4,
  sm: 8,
  md: 16,
  lg: 24,
  xl: 32,
  xxl: 48,
};

export const Radius = {
  sm: 8,
  md: 12,
  lg: 16,
  xl: 20,
  xxl: 28,
  full: 9999,
};

export const DarkColors = {
  bg: '#0D0D14',
  bgCard: '#18182A',
  bgSection: '#22223A',
  primary: '#818CF8',
  primaryDark: '#6366F1',
  primaryLight: '#1C1E42',
  primaryMid: '#2C2E5C',
  textPrimary: '#EEF0FF',
  textSecondary: '#8892A8',
  textMuted: '#515B70',
  textInverse: '#0F172A',
  danger: '#F87171',
  dangerLight: '#2D1515',
  success: '#34D399',
  successLight: '#0C2920',
  border: '#282840',
  divider: '#1C1C30',
  qrFg: '#E0E7FF',
  qrBg: '#18182A',
  test: '#F87171',
  testLight: '#2D1212',
  schedule: '#60A5FA',
  scheduleLight: '#101E38',
  homework: '#34D399',
  homeworkLight: '#0C2920',
  note: '#A78BFA',
  noteLight: '#1A1230',
};

export const Shadow = {
  card: {
    shadowColor: '#6366F1',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.07,
    shadowRadius: 10,
    elevation: 3,
  },
  fab: {
    shadowColor: '#4F46E5',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.35,
    shadowRadius: 14,
    elevation: 10,
  },
  modal: {
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.1,
    shadowRadius: 20,
    elevation: 20,
  },
};
