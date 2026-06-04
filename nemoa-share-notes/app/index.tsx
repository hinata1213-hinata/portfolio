import React, { useState, useCallback, useRef } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  Alert,
  Animated,
  Modal,
  Pressable,
} from 'react-native';
import { useFocusEffect, router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import * as Haptics from 'expo-haptics';

import { getAllMemos, deleteMemo } from '@/lib/storage';
import { Memo, MemoCategory } from '@/lib/types';
import { generateId, formatDate } from '@/lib/qr';
import { getCategoryConfig, CATEGORIES, CATEGORY_ORDER } from '@/lib/categories';
import { AppColors, Spacing, Radius, Shadow } from '@/constants/theme';
import { hasSensitiveContent } from '@/lib/filter';
import { useSettings } from '@/lib/settingsContext';

export default function HomeScreen() {
  const [memos, setMemos] = useState<Memo[]>([]);
  const [showPicker, setShowPicker] = useState(false);
  const [activeFilter, setActiveFilter] = useState<MemoCategory | 'all'>('all');
  const insets = useSafeAreaInsets();
  const { colors, isDark } = useSettings();
  const pickerSlide = useRef(new Animated.Value(400)).current;
  const pickerOpacity = useRef(new Animated.Value(0)).current;

  useFocusEffect(
    useCallback(() => {
      loadMemos();
    }, [])
  );

  async function loadMemos() {
    const data = await getAllMemos();
    setMemos(data);
  }

  function openPicker() {
    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
    setShowPicker(true);
    Animated.parallel([
      Animated.timing(pickerOpacity, { toValue: 1, duration: 200, useNativeDriver: true }),
      Animated.spring(pickerSlide, { toValue: 0, tension: 65, friction: 11, useNativeDriver: true }),
    ]).start();
  }

  function closePicker(cb?: () => void) {
    Animated.parallel([
      Animated.timing(pickerOpacity, { toValue: 0, duration: 180, useNativeDriver: true }),
      Animated.timing(pickerSlide, { toValue: 400, duration: 220, useNativeDriver: true }),
    ]).start(() => {
      setShowPicker(false);
      cb?.();
    });
  }

  function handlePickCategory(category: MemoCategory) {
    const id = generateId();
    const cat = getCategoryConfig(category);
    closePicker(() => {
      router.push(
        `/memo/${id}?isNew=1&category=${category}&template=${encodeURIComponent(cat.contentTemplate)}`
      );
    });
  }

  function handleEdit(memo: Memo) {
    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
    router.push(`/memo/${memo.id}`);
  }

  function handleScan() {
    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
    router.push('/qr/scan');
  }

  function handleSettings() {
    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
    router.push('/settings');
  }

  function confirmDelete(id: string, title: string) {
    Haptics.notificationAsync(Haptics.NotificationFeedbackType.Warning);
    Alert.alert('削除しますか？', `「${title || '無題'}」を削除します`, [
      { text: 'キャンセル', style: 'cancel' },
      {
        text: '削除',
        style: 'destructive',
        onPress: async () => {
          await deleteMemo(id);
          await loadMemos();
        },
      },
    ]);
  }

  const filteredMemos = activeFilter === 'all'
    ? memos
    : memos.filter((m) => m.category === activeFilter);

  const renderMemo = ({ item }: { item: Memo }) => (
    <MemoCard
      memo={item}
      onPress={() => handleEdit(item)}
      onDelete={() => confirmDelete(item.id, item.title)}
    />
  );

  return (
    <View style={[styles.container, { paddingTop: insets.top, backgroundColor: colors.bg }]}>
      {/* ── Header ── */}
      <View style={[styles.header, { backgroundColor: colors.bgCard }]}>
        <View>
          <Text style={[styles.headerTitle, { color: colors.textPrimary }]}>nemoa</Text>
          <Text style={[styles.headerSub, { color: colors.textMuted }]}>学校生活をQRで共有しよう</Text>
        </View>
        <View style={{ flexDirection: 'row', gap: Spacing.xs }}>
          <TouchableOpacity style={[styles.scanBtn, { backgroundColor: colors.primaryLight }]} onPress={handleScan} activeOpacity={0.75}>
            <Ionicons name="scan-outline" size={20} color={colors.primary} />
          </TouchableOpacity>
          <TouchableOpacity style={[styles.scanBtn, { backgroundColor: colors.primaryLight }]} onPress={handleSettings} activeOpacity={0.75}>
            <Ionicons name="settings-outline" size={20} color={colors.primary} />
          </TouchableOpacity>
        </View>
      </View>

      {/* ── Category filter tabs ── */}
      <View style={[styles.tabBar, { backgroundColor: colors.bgCard, borderBottomColor: colors.divider }]}>
        <FilterTab label="すべて" active={activeFilter === 'all'} onPress={() => setActiveFilter('all')} color={colors.primary} />
        {CATEGORY_ORDER.map((cat) => {
          const cfg = CATEGORIES[cat];
          return (
            <FilterTab
              key={cat}
              label={cfg.shortLabel}
              active={activeFilter === cat}
              onPress={() => setActiveFilter(cat)}
              color={cfg.color}
            />
          );
        })}
      </View>

      {/* ── List ── */}
      <FlatList
        data={filteredMemos}
        keyExtractor={(item) => item.id}
        renderItem={renderMemo}
        contentContainerStyle={[
          styles.listContent,
          filteredMemos.length === 0 && styles.listContentEmpty,
          { paddingBottom: insets.bottom + 100 },
        ]}
        ListEmptyComponent={
          <EmptyState
            filter={activeFilter}
            onNew={openPicker}
            onScan={handleScan}
          />
        }
        showsVerticalScrollIndicator={false}
      />

      {/* ── FAB ── */}
      <TouchableOpacity
        style={[styles.fab, { bottom: insets.bottom + 24 }]}
        onPress={openPicker}
        activeOpacity={0.85}
      >
        <Ionicons name="add" size={30} color="#fff" />
      </TouchableOpacity>

      {/* ── Category Picker Modal ── */}
      <Modal visible={showPicker} transparent animationType="none" onRequestClose={() => closePicker()}>
        <Animated.View style={[styles.pickerOverlay, { opacity: pickerOpacity }]}>
          <Pressable style={StyleSheet.absoluteFill} onPress={() => closePicker()} />
          <Animated.View
            style={[
              styles.pickerSheet,
              { paddingBottom: insets.bottom + Spacing.lg, backgroundColor: colors.bgCard },
              { transform: [{ translateY: pickerSlide }] },
            ]}
          >
            <View style={[styles.pickerHandle, { backgroundColor: colors.border }]} />
            <Text style={[styles.pickerTitle, { color: colors.textPrimary }]}>何を記録しますか？</Text>

            <View style={styles.pickerGrid}>
              {CATEGORY_ORDER.map((cat) => {
                const cfg = CATEGORIES[cat];
                return (
                  <TouchableOpacity
                    key={cat}
                    style={[styles.pickerCard, { backgroundColor: isDark ? cfg.color + '18' : cfg.bgColor }]}
                    onPress={() => handlePickCategory(cat)}
                    activeOpacity={0.8}
                  >
                    <View style={[styles.pickerCardIcon, { backgroundColor: cfg.color + '20' }]}>
                      <Ionicons name={cfg.icon as any} size={22} color={cfg.color} />
                    </View>
                    <Text style={[styles.pickerCardLabel, { color: cfg.color }]}>{cfg.label}</Text>
                    <Text style={[styles.pickerCardDesc, { color: colors.textSecondary }]}>{cfg.description}</Text>
                  </TouchableOpacity>
                );
              })}
            </View>

            <TouchableOpacity style={styles.pickerCancelBtn} onPress={() => closePicker()}>
              <Text style={[styles.pickerCancelText, { color: colors.textMuted }]}>キャンセル</Text>
            </TouchableOpacity>
          </Animated.View>
        </Animated.View>
      </Modal>
    </View>
  );
}

// ─── Filter Tab ───────────────────────────────────────────────────────────────

function FilterTab({
  label, active, onPress, color,
}: { label: string; active: boolean; onPress: () => void; color: string }) {
  return (
    <TouchableOpacity
      style={[
        styles.tab,
        active && { backgroundColor: color + '18', borderColor: color },
      ]}
      onPress={onPress}
      activeOpacity={0.75}
    >
      <Text style={[styles.tabText, active && { color, fontWeight: '700' }]}>{label}</Text>
    </TouchableOpacity>
  );
}

// ─── Memo Card ────────────────────────────────────────────────────────────────

function MemoCard({
  memo, onPress, onDelete,
}: { memo: Memo; onPress: () => void; onDelete: () => void }) {
  const scale = useRef(new Animated.Value(1)).current;
  const [revealed, setRevealed] = useState(false);
  const { colors, isDark } = useSettings();
  const cfg = getCategoryConfig(memo.category);
  const isSensitive = hasSensitiveContent(memo.title, memo.content);

  const handlePress = isSensitive && !revealed ? () => setRevealed(true) : onPress;
  const badgeBg = isDark ? cfg.color + '22' : cfg.bgColor;

  return (
    <Animated.View style={[styles.cardWrapper, { transform: [{ scale }] }]}>
      <TouchableOpacity
        style={[styles.card, { backgroundColor: colors.bgCard }]}
        onPress={handlePress}
        onLongPress={onDelete}
        onPressIn={() => Animated.spring(scale, { toValue: 0.975, useNativeDriver: true, speed: 50 }).start()}
        onPressOut={() => Animated.spring(scale, { toValue: 1, useNativeDriver: true, speed: 50 }).start()}
        activeOpacity={1}
        delayLongPress={500}
      >
        {/* Category accent bar */}
        <View style={[styles.cardAccent, { backgroundColor: cfg.color }]} />

        <View style={styles.cardBody}>
          {/* Top row: badge + date */}
          <View style={styles.cardTopRow}>
            <View style={styles.cardTopLeft}>
              <View style={[styles.categoryBadge, { backgroundColor: badgeBg }]}>
                <View style={[styles.categoryDot, { backgroundColor: cfg.color }]} />
                <Text style={[styles.categoryBadgeLabel, { color: cfg.color }]}>{cfg.shortLabel}</Text>
              </View>
              {isSensitive && !revealed && (
                <View style={styles.sensitiveBadge}>
                  <Ionicons name="eye-off-outline" size={10} color="#fff" />
                  <Text style={styles.sensitiveBadgeText}>センシティブ</Text>
                </View>
              )}
            </View>
            <Text style={[styles.cardDate, { color: colors.textMuted }]}>{formatDate(memo.updatedAt)}</Text>
          </View>

          {/* Title */}
          <Text
            style={[styles.cardTitle, { color: colors.textPrimary }, isSensitive && !revealed && { color: colors.textMuted }]}
            numberOfLines={1}
          >
            {isSensitive && !revealed ? '● ● ● ● ● ● ●' : memo.title || '（無題）'}
          </Text>

          {/* Content preview */}
          {isSensitive && !revealed ? (
            <View style={styles.sensitiveRevealRow}>
              <Ionicons name="eye-outline" size={12} color={colors.primary} />
              <Text style={[styles.sensitiveRevealText, { color: colors.textMuted }]}>タップして内容を表示</Text>
            </View>
          ) : memo.content.trim().length > 0 && (
            <Text style={[styles.cardPreview, { color: colors.textSecondary }]} numberOfLines={2}>
              {memo.content.replace(/\n/g, ' ')}
            </Text>
          )}
        </View>

        <Ionicons
          name={isSensitive && !revealed ? 'lock-closed-outline' : 'chevron-forward'}
          size={16}
          color={isSensitive && !revealed ? '#F59E0B' : colors.textMuted}
          style={styles.cardChevron}
        />
      </TouchableOpacity>
    </Animated.View>
  );
}

// ─── Empty State ──────────────────────────────────────────────────────────────

function EmptyState({
  filter, onNew, onScan,
}: { filter: MemoCategory | 'all'; onNew: () => void; onScan: () => void }) {
  const { colors } = useSettings();
  const isFiltered = filter !== 'all';
  const cfg = isFiltered ? getCategoryConfig(filter as MemoCategory) : null;

  return (
    <View style={styles.emptyContainer}>
      <View style={[styles.emptyIconWrap, { backgroundColor: cfg ? cfg.bgColor : AppColors.divider }]}>
        <Ionicons name={cfg ? (cfg.icon as any) : 'document-outline'} size={36} color={cfg ? cfg.color : AppColors.textMuted} />
      </View>
      <Text style={[styles.emptyTitle, { color: colors.textPrimary }]}>
        {isFiltered ? `${cfg!.label}のメモがありません` : 'メモがありません'}
      </Text>
      <Text style={[styles.emptyText, { color: colors.textSecondary }]}>
        {isFiltered
          ? `${cfg!.label}を作成してQRコードで共有しよう`
          : 'テスト情報・時間割・宿題をQRコードで友達とすぐに共有できます'}
      </Text>
      <View style={styles.emptyActions}>
        <TouchableOpacity style={styles.emptyBtnPrimary} onPress={onNew} activeOpacity={0.85}>
          <Ionicons name="add-circle-outline" size={17} color="#fff" />
          <Text style={styles.emptyBtnPrimaryText}>メモを作成</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.emptyBtnOutline} onPress={onScan} activeOpacity={0.85}>
          <Ionicons name="scan-outline" size={17} color={AppColors.primary} />
          <Text style={styles.emptyBtnOutlineText}>QRスキャン</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: AppColors.bg },

  // Header
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: Spacing.lg,
    paddingTop: Spacing.sm,
    paddingBottom: Spacing.md,
    backgroundColor: AppColors.bgCard,
  },
  headerTitle: {
    fontSize: 22,
    fontWeight: '800',
    color: AppColors.textPrimary,
    letterSpacing: -0.4,
  },
  headerSub: { fontSize: 12, color: AppColors.textMuted, marginTop: 2 },
  scanBtn: {
    width: 42,
    height: 42,
    borderRadius: Radius.md,
    backgroundColor: AppColors.primaryLight,
    alignItems: 'center',
    justifyContent: 'center',
  },

  // Tab bar
  tabBar: {
    flexDirection: 'row',
    paddingHorizontal: Spacing.lg,
    paddingVertical: Spacing.sm,
    gap: Spacing.xs,
    backgroundColor: AppColors.bgCard,
    borderBottomWidth: 1,
    borderBottomColor: AppColors.divider,
  },
  tab: {
    paddingHorizontal: Spacing.sm + 2,
    paddingVertical: Spacing.xs + 1,
    borderRadius: Radius.full,
    borderWidth: 1,
    borderColor: AppColors.border,
  },
  tabText: {
    fontSize: 12,
    fontWeight: '500',
    color: AppColors.textMuted,
  },

  // List
  listContent: { paddingHorizontal: Spacing.lg, paddingTop: Spacing.md },
  listContentEmpty: { flex: 1 },

  // Card
  cardWrapper: { marginBottom: Spacing.sm },
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: AppColors.bgCard,
    borderRadius: Radius.lg,
    overflow: 'hidden',
    ...Shadow.card,
  },
  cardAccent: { width: 4, alignSelf: 'stretch' },
  cardBody: { flex: 1, paddingVertical: Spacing.md - 2, paddingHorizontal: Spacing.md, gap: 5 },
  cardTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  cardTopLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.xs,
    flexShrink: 1,
  },
  sensitiveBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 3,
    backgroundColor: '#F59E0B',
    paddingHorizontal: 5,
    paddingVertical: 2,
    borderRadius: Radius.sm,
  },
  sensitiveBadgeText: { fontSize: 9, fontWeight: '700', color: '#fff' },
  sensitiveRevealRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingVertical: 2,
  },
  sensitiveRevealText: { fontSize: 12, color: AppColors.textMuted, fontStyle: 'italic' },
  categoryBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: 7,
    paddingVertical: 3,
    borderRadius: Radius.sm,
  },
  categoryDot: { width: 6, height: 6, borderRadius: 3 },
  categoryBadgeLabel: { fontSize: 11, fontWeight: '700' },
  cardTitleMasked: { color: AppColors.textMuted, letterSpacing: 2 },
  cardDate: { fontSize: 11, color: AppColors.textMuted },
  cardTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: AppColors.textPrimary,
    letterSpacing: -0.1,
  },
  cardPreview: { fontSize: 13, color: AppColors.textSecondary, lineHeight: 19 },
  cardChevron: { marginRight: Spacing.md },

  // FAB
  fab: {
    position: 'absolute',
    right: Spacing.lg,
    width: 58,
    height: 58,
    borderRadius: Radius.full,
    backgroundColor: AppColors.primary,
    alignItems: 'center',
    justifyContent: 'center',
    ...Shadow.fab,
  },

  // Picker Modal
  pickerOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.45)',
    justifyContent: 'flex-end',
  },
  pickerSheet: {
    backgroundColor: AppColors.bgCard,
    borderTopLeftRadius: Radius.xxl,
    borderTopRightRadius: Radius.xxl,
    paddingTop: Spacing.sm,
    paddingHorizontal: Spacing.lg,
    ...Shadow.modal,
  },
  pickerHandle: {
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: AppColors.border,
    alignSelf: 'center',
    marginBottom: Spacing.md,
  },
  pickerTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: AppColors.textPrimary,
    textAlign: 'center',
    marginBottom: Spacing.lg,
    letterSpacing: -0.3,
  },
  pickerGrid: {
    flexDirection: 'row',
    gap: Spacing.sm,
    marginBottom: Spacing.md,
  },
  pickerCard: {
    flex: 1,
    minWidth: 0,
    borderRadius: Radius.lg,
    padding: Spacing.md,
    gap: Spacing.xs,
  },
  pickerCardIcon: {
    width: 44,
    height: 44,
    borderRadius: Radius.md,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 2,
  },
  pickerEmoji: { fontSize: 22 },
  pickerCardLabel: { fontSize: 14, fontWeight: '800' },
  pickerCardDesc: {
    fontSize: 11,
    color: AppColors.textSecondary,
    lineHeight: 16,
  },
  pickerCancelBtn: {
    alignItems: 'center',
    paddingVertical: Spacing.md,
  },
  pickerCancelText: { fontSize: 15, color: AppColors.textMuted, fontWeight: '500' },

  // Empty
  emptyContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: Spacing.xl,
    paddingVertical: Spacing.xxl,
    gap: Spacing.sm,
  },
  emptyIconWrap: {
    width: 80,
    height: 80,
    borderRadius: Radius.xxl,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: Spacing.xs,
  },
  emptyTitle: { fontSize: 18, fontWeight: '700', color: AppColors.textPrimary },
  emptyText: {
    fontSize: 13,
    color: AppColors.textSecondary,
    textAlign: 'center',
    lineHeight: 20,
    marginBottom: Spacing.md,
  },
  emptyActions: { flexDirection: 'row', gap: Spacing.sm },
  emptyBtnPrimary: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.xs,
    backgroundColor: AppColors.primary,
    paddingHorizontal: Spacing.md,
    paddingVertical: Spacing.sm + 2,
    borderRadius: Radius.full,
    ...Shadow.fab,
  },
  emptyBtnPrimaryText: { fontSize: 14, fontWeight: '700', color: '#fff' },
  emptyBtnOutline: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.xs,
    backgroundColor: AppColors.bgCard,
    paddingHorizontal: Spacing.md,
    paddingVertical: Spacing.sm + 2,
    borderRadius: Radius.full,
    borderWidth: 1.5,
    borderColor: AppColors.primary,
  },
  emptyBtnOutlineText: { fontSize: 14, fontWeight: '700', color: AppColors.primary },
});
