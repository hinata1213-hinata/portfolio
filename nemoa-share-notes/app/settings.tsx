import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  Switch,
  TouchableOpacity,
  ScrollView,
} from 'react-native';
import { router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import * as Haptics from 'expo-haptics';

import { useSettings } from '@/lib/settingsContext';
import { Spacing, Radius, Shadow } from '@/constants/theme';

export default function SettingsScreen() {
  const insets = useSafeAreaInsets();
  const { settings, update, colors, isDark } = useSettings();

  function toggle(key: keyof typeof settings) {
    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
    update(key, !settings[key] as any);
  }

  return (
    <View style={[styles.root, { backgroundColor: colors.bg }]}>
      {/* ── Header ── */}
      <View
        style={[
          styles.header,
          { paddingTop: insets.top + Spacing.sm, backgroundColor: colors.bgCard, borderBottomColor: colors.border },
        ]}
      >
        <TouchableOpacity style={styles.backBtn} onPress={() => router.back()} activeOpacity={0.75}>
          <Ionicons name="chevron-back" size={22} color={colors.primary} />
        </TouchableOpacity>
        <Text style={[styles.headerTitle, { color: colors.textPrimary }]}>設定</Text>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView
        contentContainerStyle={[styles.scroll, { paddingBottom: insets.bottom + Spacing.xxl }]}
        showsVerticalScrollIndicator={false}
      >
        {/* ── 外観 ── */}
        <SectionLabel label="外観" colors={colors} />
        <View style={[styles.card, { backgroundColor: colors.bgCard, borderColor: colors.border }]}>
          <SettingRow
            icon="moon-outline"
            iconColor="#818CF8"
            iconBg={isDark ? '#1C1E42' : '#EEF2FF'}
            label="ダークモード"
            desc="アプリの配色を暗くします"
            value={settings.darkMode}
            onToggle={() => toggle('darkMode')}
            colors={colors}
            last
          />
        </View>

        {/* ── プライバシー & セーフティ ── */}
        <SectionLabel label="プライバシー & セーフティ" colors={colors} />
        <View style={[styles.card, { backgroundColor: colors.bgCard, borderColor: colors.border }]}>
          <SettingRow
            icon="eye-off-outline"
            iconColor="#F59E0B"
            iconBg={isDark ? '#2A1F0A' : '#FEF3C7'}
            label="センシティブコンテンツを開かない"
            desc="QRスキャン時にセンシティブな内容を保存・閲覧できないようにします"
            value={settings.blockSensitiveOpen}
            onToggle={() => toggle('blockSensitiveOpen')}
            colors={colors}
            last
          />
        </View>

        {/* ── このアプリについて ── */}
        <SectionLabel label="このアプリについて" colors={colors} />
        <View style={[styles.card, { backgroundColor: colors.bgCard, borderColor: colors.border }]}>
          <InfoRow
            icon="qr-code-outline"
            iconColor={colors.primary}
            iconBg={isDark ? '#1C1E42' : '#EEF2FF'}
            label="nemoa"
            value="ver 1.0.0"
            colors={colors}
          />
          <View style={[styles.divider, { backgroundColor: colors.divider }]} />
          <InfoRow
            icon="school-outline"
            iconColor="#10B981"
            iconBg={isDark ? '#0C2920' : '#D1FAE5'}
            label="用途"
            value="学生向けメモ共有"
            colors={colors}
            last
          />
        </View>
      </ScrollView>
    </View>
  );
}

// ─── Sub Components ────────────────────────────────────────────────────────────

function SectionLabel({ label, colors }: { label: string; colors: any }) {
  return (
    <Text style={[styles.sectionLabel, { color: colors.textMuted }]}>{label}</Text>
  );
}

function SettingRow({
  icon, iconColor, iconBg, label, desc, value, onToggle, colors, last,
}: {
  icon: string; iconColor: string; iconBg: string;
  label: string; desc: string;
  value: boolean; onToggle: () => void;
  colors: any; last?: boolean;
}) {
  return (
    <View style={[styles.row, last && styles.rowLast]}>
      <View style={[styles.iconWrap, { backgroundColor: iconBg }]}>
        <Ionicons name={icon as any} size={18} color={iconColor} />
      </View>
      <View style={styles.rowContent}>
        <Text style={[styles.rowLabel, { color: colors.textPrimary }]}>{label}</Text>
        <Text style={[styles.rowDesc, { color: colors.textMuted }]}>{desc}</Text>
      </View>
      <Switch
        value={value}
        onValueChange={onToggle}
        trackColor={{ false: colors.border, true: '#818CF8' }}
        thumbColor="#fff"
        ios_backgroundColor={colors.border}
      />
    </View>
  );
}

function InfoRow({
  icon, iconColor, iconBg, label, value, colors, last,
}: {
  icon: string; iconColor: string; iconBg: string;
  label: string; value: string;
  colors: any; last?: boolean;
}) {
  return (
    <View style={[styles.row, last && styles.rowLast]}>
      <View style={[styles.iconWrap, { backgroundColor: iconBg }]}>
        <Ionicons name={icon as any} size={18} color={iconColor} />
      </View>
      <View style={styles.rowContent}>
        <Text style={[styles.rowLabel, { color: colors.textPrimary }]}>{label}</Text>
      </View>
      <Text style={[styles.rowValue, { color: colors.textMuted }]}>{value}</Text>
    </View>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const styles = StyleSheet.create({
  root: { flex: 1 },

  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: Spacing.md,
    paddingBottom: Spacing.md,
    borderBottomWidth: 1,
    ...Shadow.card,
  },
  backBtn: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: Radius.md,
  },
  headerTitle: {
    fontSize: 17,
    fontWeight: '700',
    letterSpacing: -0.2,
  },

  scroll: {
    paddingHorizontal: Spacing.lg,
    paddingTop: Spacing.lg,
    gap: Spacing.xs,
  },

  sectionLabel: {
    fontSize: 12,
    fontWeight: '700',
    letterSpacing: 0.6,
    textTransform: 'uppercase',
    marginTop: Spacing.lg,
    marginBottom: Spacing.xs,
    marginLeft: Spacing.xs,
  },

  card: {
    borderRadius: Radius.xl,
    borderWidth: 1,
    overflow: 'hidden',
    ...Shadow.card,
  },

  divider: { height: StyleSheet.hairlineWidth, marginLeft: 64 },

  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.md,
    paddingHorizontal: Spacing.md,
    paddingVertical: Spacing.md,
    minHeight: 64,
  },
  rowLast: {},

  iconWrap: {
    width: 36,
    height: 36,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    flexShrink: 0,
  },

  rowContent: { flex: 1, gap: 2 },
  rowLabel: { fontSize: 15, fontWeight: '600', letterSpacing: -0.1 },
  rowDesc: { fontSize: 12, lineHeight: 17 },
  rowValue: { fontSize: 14, fontWeight: '500' },
});
