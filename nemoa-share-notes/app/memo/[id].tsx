import React, { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  TextInput,
  StyleSheet,
  TouchableOpacity,
  ScrollView,
  Alert,
  KeyboardAvoidingView,
  Platform,
  Animated,
} from 'react-native';
import { useLocalSearchParams, router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import * as Haptics from 'expo-haptics';

import { getMemoById, saveMemo, deleteMemo } from '@/lib/storage';
import { Memo, MemoCategory } from '@/lib/types';
import { generateId, formatDateTime } from '@/lib/qr';
import { getCategoryConfig } from '@/lib/categories';
import { AppColors, Spacing, Radius, Shadow } from '@/constants/theme';
import { hasSensitiveContent } from '@/lib/filter';
import { useSettings } from '@/lib/settingsContext';

const MAX_CHARS = 1500;

export default function MemoEditScreen() {
  const { id, isNew, category, template } = useLocalSearchParams<{
    id: string;
    isNew?: string;
    category?: MemoCategory;
    template?: string;
  }>();
  const insets = useSafeAreaInsets();
  const saveScale = useRef(new Animated.Value(1)).current;

  const resolvedCategory: MemoCategory = category ?? 'note';
  const cfg = getCategoryConfig(resolvedCategory);
  const { colors, isDark } = useSettings();
  const [memoSensitive, setMemoSensitive] = useState(false);
  const [revealedEdit, setRevealedEdit] = useState(false);

  const [memo, setMemo] = useState<Memo>({
    id: id ?? generateId(),
    title: '',
    content: template ? decodeURIComponent(template) : '',
    category: resolvedCategory,
    createdAt: Date.now(),
    updatedAt: Date.now(),
  });
  const [isSaved, setIsSaved] = useState(isNew !== '1');
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    if (isNew !== '1' && id) {
      loadMemo();
    }
  }, [id]);

  async function loadMemo() {
    const data = await getMemoById(id!);
    if (data) {
      setMemo(data);
      setMemoSensitive(hasSensitiveContent(data.title, data.content));
    }
  }

  function updateTitle(text: string) {
    setMemo((prev) => ({ ...prev, title: text }));
    setIsSaved(false);
  }

  function updateContent(text: string) {
    setMemo((prev) => ({ ...prev, content: text }));
    setIsSaved(false);
  }

  async function handleSave() {
    if (!memo.title.trim() && !memo.content.trim()) {
      Alert.alert('入力してください', 'タイトルまたは内容を入力してください');
      return;
    }
    setIsSaving(true);
    Animated.sequence([
      Animated.spring(saveScale, { toValue: 0.92, useNativeDriver: true, speed: 60 }),
      Animated.spring(saveScale, { toValue: 1, useNativeDriver: true, speed: 60 }),
    ]).start();

    const updated: Memo = { ...memo, updatedAt: Date.now() };
    await saveMemo(updated);
    setMemo(updated);
    setIsSaved(true);
    setIsSaving(false);
    await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
  }

  async function handleDelete() {
    if (isNew === '1' && !memo.title && !memo.content) {
      router.back();
      return;
    }
    const runDelete = async () => {
      await deleteMemo(memo.id);
      await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Warning);
      router.back();
    };

    // Web では Alert.alert の複数ボタンが動かないため window.confirm を使う
    if (Platform.OS === 'web') {
      if (window.confirm('このメモを削除します')) {
        runDelete();
      }
      return;
    }

    Alert.alert('削除しますか？', 'このメモを削除します', [
      { text: 'キャンセル', style: 'cancel' },
      { text: '削除', style: 'destructive', onPress: runDelete },
    ]);
  }

  function handleShowQR() {
    if (!isSaved) {
      Alert.alert('先に保存してください', 'QRコードを生成するにはメモを保存してください', [
        { text: 'キャンセル', style: 'cancel' },
        {
          text: '保存してQR表示',
          onPress: async () => {
            await handleSave();
            router.push(`/qr/show?id=${memo.id}`);
          },
        },
      ]);
      return;
    }
    router.push(`/qr/show?id=${memo.id}`);
  }

  const charCount = memo.title.length + memo.content.length;
  const isOverLimit = charCount > MAX_CHARS;
  // Determine accent color from category
  const accentColor = cfg.color;

  const chipBg = isDark ? cfg.color + '22' : cfg.bgColor;
  const tipBg  = isDark ? cfg.color + '18' : cfg.bgColor;
  const maskOverlayBg = isDark ? 'rgba(15,15,22,0.90)' : 'rgba(248,248,252,0.88)';

  return (
    <KeyboardAvoidingView
      style={{ flex: 1 }}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <View style={[styles.container, { paddingTop: insets.top, backgroundColor: colors.bg }]}>
        {/* ── Header ── */}
        <View style={[styles.header, { backgroundColor: colors.bgCard, borderBottomColor: accentColor + '30' }]}>
          <TouchableOpacity style={styles.headerBtn} onPress={() => router.back()}>
            <Ionicons name="chevron-back" size={22} color={colors.textPrimary} />
          </TouchableOpacity>

          {/* Category chip */}
          <View style={[styles.categoryChip, { backgroundColor: chipBg }]}>
            <Ionicons name={cfg.icon as any} size={14} color={cfg.color} />
            <Text style={[styles.categoryChipLabel, { color: cfg.color }]}>{cfg.editLabel}</Text>
          </View>

          <TouchableOpacity style={styles.headerBtn} onPress={handleDelete}>
            <Ionicons name="trash-outline" size={19} color={AppColors.danger} />
          </TouchableOpacity>
        </View>

        {/* ── Form ── */}
        <ScrollView
          style={[styles.scrollView, { backgroundColor: colors.bg }]}
          contentContainerStyle={[styles.scrollContent, { paddingBottom: insets.bottom + 130 }]}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          {memoSensitive && !revealedEdit ? (
            /* ── センシティブマスク ── */
            <View style={[styles.editMaskContainer, { backgroundColor: colors.divider }]}>
              <View style={styles.editMosaicBg}>
                {Array.from({ length: 8 }).map((_, row) => (
                  <View key={row} style={styles.editMosaicRow}>
                    {Array.from({ length: 10 }).map((_, col) => (
                      <View
                        key={col}
                        style={[
                          styles.editMosaicCell,
                          { backgroundColor: (row + col) % 2 === 0 ? 'rgba(150,150,165,0.18)' : 'rgba(200,200,215,0.12)' },
                        ]}
                      />
                    ))}
                  </View>
                ))}
              </View>
              <View style={[styles.editMaskOverlay, { backgroundColor: maskOverlayBg }]}>
                <View style={[styles.editMaskIconWrap, { backgroundColor: colors.divider }]}>
                  <Ionicons name="eye-off-outline" size={32} color={colors.textMuted} />
                </View>
                <Text style={[styles.editMaskTitle, { color: colors.textPrimary }]}>センシティブなコンテンツ</Text>
                <Text style={[styles.editMaskSub, { color: colors.textSecondary }]}>このメモにはセンシティブな表現が含まれています</Text>
                <TouchableOpacity
                  style={[styles.editMaskBtn, { backgroundColor: accentColor }]}
                  onPress={() => setRevealedEdit(true)}
                  activeOpacity={0.85}
                >
                  <Ionicons name="eye-outline" size={16} color="#fff" />
                  <Text style={styles.editMaskBtnText}>タップして表示する</Text>
                </TouchableOpacity>
              </View>
            </View>
          ) : (
            <>
              {/* Title */}
              <View style={styles.fieldGroup}>
                <Text style={[styles.fieldLabel, { color: accentColor }]}>タイトル</Text>
                <TextInput
                  style={[styles.titleInput, { borderColor: accentColor + '40', backgroundColor: colors.bgCard, color: colors.textPrimary }]}
                  value={memo.title}
                  onChangeText={updateTitle}
                  placeholder={cfg.titlePlaceholder}
                  placeholderTextColor={colors.textMuted}
                  maxLength={200}
                  returnKeyType="next"
                />
              </View>

              {/* Content */}
              <View style={styles.fieldGroup}>
                <View style={styles.fieldLabelRow}>
                  <Text style={[styles.fieldLabel, { color: accentColor }]}>内容</Text>
                  <Text style={[styles.charCount, { color: colors.textMuted }, isOverLimit && styles.charCountOver]}>
                    {charCount} / {MAX_CHARS}
                  </Text>
                </View>
                <TextInput
                  style={[styles.contentInput, { borderColor: accentColor + '40', backgroundColor: colors.bgCard, color: colors.textPrimary }]}
                  value={memo.content}
                  onChangeText={updateContent}
                  placeholder="内容を入力..."
                  placeholderTextColor={colors.textMuted}
                  multiline
                  textAlignVertical="top"
                  maxLength={MAX_CHARS + 100}
                />
              </View>
            </>
          )}

          {/* Tips by category (new memo only) */}
          {isNew === '1' && cfg.id !== 'note' && (
            <View style={[styles.tipBox, { backgroundColor: tipBg, borderColor: cfg.color + '30' }]}>
              <Ionicons name="bulb-outline" size={16} color={cfg.color} />
              <Text style={[styles.tipText, { color: cfg.color }]}>
                {cfg.id === 'test' && 'テストの日付・科目・範囲を入力してQRで友達に共有しよう'}
                {cfg.id === 'homework' && '提出日と課題内容を入力して忘れないようにしよう'}
              </Text>
            </View>
          )}

          {/* Timestamps (existing memos) */}
          {isNew !== '1' && (
            <View style={styles.timestamps}>
              <View style={styles.timestampRow}>
                <Ionicons name="time-outline" size={12} color={colors.textMuted} />
                <Text style={[styles.timestampText, { color: colors.textMuted }]}>作成: {formatDateTime(memo.createdAt)}</Text>
              </View>
              <View style={styles.timestampRow}>
                <Ionicons name="pencil-outline" size={12} color={colors.textMuted} />
                <Text style={[styles.timestampText, { color: colors.textMuted }]}>更新: {formatDateTime(memo.updatedAt)}</Text>
              </View>
            </View>
          )}
        </ScrollView>

        {/* ── Bottom Bar ── */}
        <View style={[styles.bottomBar, { paddingBottom: insets.bottom + 12, backgroundColor: colors.bgCard, borderTopColor: colors.border }]}>
          {/* QR button */}
          <TouchableOpacity
            style={[
              styles.qrBtn,
              isSaved
                ? { borderColor: accentColor, backgroundColor: chipBg }
                : { borderColor: colors.border, backgroundColor: colors.divider },
            ]}
            onPress={handleShowQR}
            activeOpacity={0.8}
          >
            <Ionicons name="qr-code-outline" size={20} color={isSaved ? accentColor : colors.textMuted} />
            <Text style={[styles.qrBtnText, { color: isSaved ? accentColor : colors.textMuted }]}>
              QRコード
            </Text>
          </TouchableOpacity>

          {/* Save button */}
          <Animated.View style={[{ flex: 1, transform: [{ scale: saveScale }] }]}>
            <TouchableOpacity
              style={[
                styles.saveBtn,
                { backgroundColor: isSaved ? AppColors.success : accentColor },
                isOverLimit && styles.saveBtnDisabled,
              ]}
              onPress={handleSave}
              activeOpacity={0.85}
              disabled={isSaving || isOverLimit}
            >
              <Ionicons name={isSaved ? 'checkmark-circle' : 'save-outline'} size={20} color="#fff" />
              <Text style={styles.saveBtnText}>{isSaved ? '保存済み' : '保存する'}</Text>
            </TouchableOpacity>
          </Animated.View>
        </View>
      </View>
    </KeyboardAvoidingView>
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
    paddingHorizontal: Spacing.md,
    paddingVertical: Spacing.sm + 4,
    backgroundColor: AppColors.bgCard,
    borderBottomWidth: 1.5,
  },
  headerBtn: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: Radius.md,
  },
  categoryChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.xs,
    paddingHorizontal: Spacing.md,
    paddingVertical: Spacing.xs + 2,
    borderRadius: Radius.full,
  },
  categoryChipEmoji: { fontSize: 16 },
  categoryChipLabel: { fontSize: 14, fontWeight: '800' },

  // Scroll
  scrollView: { flex: 1 },
  scrollContent: { padding: Spacing.lg, gap: Spacing.lg },

  // Fields
  fieldGroup: { gap: Spacing.xs + 2 },
  fieldLabelRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  fieldLabel: {
    fontSize: 12,
    fontWeight: '800',
    textTransform: 'uppercase',
    letterSpacing: 0.8,
  },
  charCount: { fontSize: 11, color: AppColors.textMuted },
  charCountOver: { color: AppColors.danger, fontWeight: '700' },
  titleInput: {
    backgroundColor: AppColors.bgCard,
    borderRadius: Radius.md,
    borderWidth: 1.5,
    paddingHorizontal: Spacing.md,
    paddingVertical: Spacing.md - 2,
    fontSize: 17,
    fontWeight: '600',
    color: AppColors.textPrimary,
    ...Shadow.card,
  },
  contentInput: {
    backgroundColor: AppColors.bgCard,
    borderRadius: Radius.md,
    borderWidth: 1.5,
    paddingHorizontal: Spacing.md,
    paddingVertical: Spacing.md,
    fontSize: 15,
    color: AppColors.textPrimary,
    lineHeight: 24,
    minHeight: 200,
    ...Shadow.card,
  },

  // Tip box
  tipBox: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: Spacing.xs,
    padding: Spacing.md,
    borderRadius: Radius.md,
    borderWidth: 1,
  },
  tipText: { flex: 1, fontSize: 13, lineHeight: 20, fontWeight: '500' },

  // Timestamps
  timestamps: { gap: Spacing.xs, paddingHorizontal: Spacing.xs },
  timestampRow: { flexDirection: 'row', alignItems: 'center', gap: Spacing.xs },
  timestampText: { fontSize: 12, color: AppColors.textMuted },

  // Bottom bar
  bottomBar: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: Spacing.lg,
    paddingTop: Spacing.md,
    gap: Spacing.sm,
    backgroundColor: AppColors.bgCard,
    borderTopWidth: 1,
    borderTopColor: AppColors.border,
    ...Shadow.modal,
  },
  qrBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.xs,
    paddingHorizontal: Spacing.md,
    paddingVertical: Spacing.sm + 4,
    borderRadius: Radius.lg,
    borderWidth: 1.5,
  },
  qrBtnText: { fontSize: 14, fontWeight: '600' },
  saveBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: Spacing.xs,
    paddingVertical: Spacing.sm + 4,
    borderRadius: Radius.lg,
    ...Shadow.fab,
  },
  saveBtnDisabled: {
    backgroundColor: AppColors.textMuted,
    shadowOpacity: 0,
    elevation: 0,
  },
  saveBtnText: { fontSize: 15, fontWeight: '700', color: '#fff' },

  // Edit screen sensitive mask
  editMaskContainer: {
    borderRadius: Radius.lg,
    overflow: 'hidden',
    minHeight: 280,
    backgroundColor: AppColors.divider,
  },
  editMosaicBg: {
    position: 'absolute',
    top: 0, left: 0, right: 0, bottom: 0,
    flexDirection: 'column',
  },
  editMosaicRow: { flex: 1, flexDirection: 'row' },
  editMosaicCell: { flex: 1 },
  editMaskOverlay: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: Spacing.sm,
    paddingVertical: Spacing.xxl,
    paddingHorizontal: Spacing.xl,
    backgroundColor: 'rgba(248,248,252,0.88)',
  },
  editMaskIconWrap: {
    width: 64,
    height: 64,
    borderRadius: Radius.xxl,
    backgroundColor: AppColors.divider,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: Spacing.xs,
  },
  editMaskTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: AppColors.textPrimary,
    letterSpacing: -0.2,
  },
  editMaskSub: {
    fontSize: 13,
    color: AppColors.textSecondary,
    textAlign: 'center',
    lineHeight: 20,
    marginBottom: Spacing.xs,
  },
  editMaskBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.xs,
    paddingHorizontal: Spacing.lg,
    paddingVertical: Spacing.sm + 2,
    borderRadius: Radius.full,
    ...Shadow.fab,
  },
  editMaskBtnText: { fontSize: 14, fontWeight: '700', color: '#fff' },
});
