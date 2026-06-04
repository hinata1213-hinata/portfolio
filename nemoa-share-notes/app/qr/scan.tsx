import React, { useState, useRef } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ScrollView,
  Animated,
  Dimensions,
  ActivityIndicator,
} from 'react-native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import * as Haptics from 'expo-haptics';

import { saveMemo } from '@/lib/storage';
import { decodeQR, generateId } from '@/lib/qr';
import { Memo } from '@/lib/types';
import { getCategoryConfig } from '@/lib/categories';
import { AppColors, Spacing, Radius, Shadow } from '@/constants/theme';
import { hasSensitiveContent } from '@/lib/filter';
import { useSettings } from '@/lib/settingsContext';

const { width: SCREEN_WIDTH, height: SCREEN_HEIGHT } = Dimensions.get('window');
const FRAME_SIZE = SCREEN_WIDTH * 0.68;
const FRAME_CENTER_Y = SCREEN_HEIGHT * 0.42;

export default function QRScanScreen() {
  const [permission, requestPermission] = useCameraPermissions();
  const insets = useSafeAreaInsets();
  const { settings } = useSettings();

  const [scanned, setScanned] = useState(false);
  const [cameraState, setCameraState] = useState<'active' | 'error' | 'restarting'>('active');
  const [blockedBySensitive, setBlockedBySensitive] = useState(false);
  const [previewMemo, setPreviewMemo] = useState<Memo | null>(null);
  const [showPreview, setShowPreview] = useState(false);
  const [titleRevealed, setTitleRevealed] = useState(false);
  const [contentRevealed, setContentRevealed] = useState(false);
  const slideAnim = useRef(new Animated.Value(400)).current;

  function showModal() {
    setShowPreview(true);
    Animated.spring(slideAnim, {
      toValue: 0,
      useNativeDriver: true,
      tension: 65,
      friction: 11,
    }).start();
  }

  function resetScan() {
    setBlockedBySensitive(false);
    setCameraState('restarting');
    setScanned(false);
    setTimeout(() => setCameraState('active'), 700);
  }

  function hideModal(cb?: () => void) {
    Animated.timing(slideAnim, {
      toValue: 400,
      duration: 220,
      useNativeDriver: true,
    }).start(() => {
      setShowPreview(false);
      cb?.();
    });
  }

  async function handleBarcodeScanned({ data }: { data: string }) {
    if (scanned) return;
    setScanned(true);

    const qrData = decodeQR(data);
    if (!qrData) {
      await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
      setCameraState('error');
      return;
    }

    await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
    const now = Date.now();
    const memo: Memo = {
      id: generateId(),
      title: qrData.t,
      content: qrData.c,
      category: qrData.k ?? 'note',
      createdAt: now,
      updatedAt: qrData.u ?? now,
    };

    // センシティブブロック設定が ON の場合は保存・閲覧不可
    if (settings.blockSensitiveOpen && hasSensitiveContent(memo.title, memo.content)) {
      await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
      setCameraState('error');
      setBlockedBySensitive(true);
      return;
    }

    setPreviewMemo(memo);
    showModal();
  }

  async function handleSave() {
    if (!previewMemo) return;
    await saveMemo(previewMemo);
    await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
    const savedId = previewMemo.id;
    hideModal(() => {
      router.replace('/');
      setTimeout(() => {
        router.push(`/memo/${savedId}`);
      }, 350);
    });
  }

  function handleCancel() {
    hideModal(() => {
      setScanned(false);
      setPreviewMemo(null);
      setTitleRevealed(false);
      setContentRevealed(false);
    });
  }

  // Permission denied
  if (!permission) {
    return <View style={[styles.container, { paddingTop: insets.top }]} />;
  }

  if (!permission.granted) {
    return (
      <View style={[styles.permContainer, { paddingTop: insets.top }]}>
        <View style={styles.permIcon}>
          <Ionicons name="camera-outline" size={48} color={AppColors.primary} />
        </View>
        <Text style={styles.permTitle}>カメラへのアクセスが必要です</Text>
        <Text style={styles.permText}>
          QRコードをスキャンするには{'\n'}カメラの使用を許可してください
        </Text>
        <TouchableOpacity style={styles.permBtn} onPress={requestPermission} activeOpacity={0.85}>
          <Text style={styles.permBtnText}>アクセスを許可</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.permCancelBtn} onPress={() => router.back()}>
          <Text style={styles.permCancelText}>キャンセル</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Camera — error中はアンマウントしてカメラを完全停止 */}
      {cameraState !== 'error' && (
        <CameraView
          style={StyleSheet.absoluteFill}
          barcodeScannerSettings={{ barcodeTypes: ['qr'] }}
          onBarcodeScanned={cameraState === 'active' && !scanned ? handleBarcodeScanned : undefined}
        />
      )}

      {/* Dark overlay with cutout — スキャン中のみ表示 */}
      {cameraState === 'active' && (
        <>
          <View style={styles.overlayTop} />
          <View style={styles.overlayMiddle}>
            <View style={styles.overlaySide} />
            <View style={styles.scanFrame}>
              <View style={[styles.bracket, styles.bracketTL]} />
              <View style={[styles.bracket, styles.bracketTR]} />
              <View style={[styles.bracket, styles.bracketBL]} />
              <View style={[styles.bracket, styles.bracketBR]} />
            </View>
            <View style={styles.overlaySide} />
          </View>
          <View style={styles.overlayBottom} />
        </>
      )}

      {/* Close button */}
      <View style={[styles.topBar, { paddingTop: insets.top + Spacing.sm }]}>
        <TouchableOpacity
          style={styles.closeBtn}
          onPress={() => router.back()}
          activeOpacity={0.8}
        >
          <Ionicons name="close" size={22} color="#fff" />
        </TouchableOpacity>
        <Text style={styles.topTitle}>QRコードをスキャン</Text>
        <View style={{ width: 44 }} />
      </View>

      {/* Hint text — スキャン中のみ */}
      {cameraState === 'active' && (
        <View style={styles.hintBar}>
          <Text style={styles.hintText}>
            nemoaのQRコードをフレーム内に合わせてください
          </Text>
        </View>
      )}

      {/* Restarting overlay — カメラが温まるまでローディング表示 */}
      {cameraState === 'restarting' && (
        <View style={styles.restartingOverlay}>
          <ActivityIndicator size="large" color="#fff" />
          <Text style={styles.restartingText}>カメラを起動中...</Text>
        </View>
      )}

      {/* Error overlay — カメラ停止中 */}
      {cameraState === 'error' && (
        <View style={styles.errorOverlay}>
          <View style={styles.errorCard}>
            <View style={[styles.errorIconWrap, blockedBySensitive && styles.errorIconWrapBlock]}>
              <Ionicons
                name={blockedBySensitive ? 'shield-outline' : 'alert-circle-outline'}
                size={40}
                color={blockedBySensitive ? '#F59E0B' : '#EF4444'}
              />
            </View>
            <Text style={styles.errorTitle}>
              {blockedBySensitive ? 'コンテンツをブロック' : '読み取りエラー'}
            </Text>
            <Text style={styles.errorSub}>
              {blockedBySensitive
                ? 'センシティブな内容が含まれているため\n設定によりブロックされました'
                : 'nemoaのQRコードではありません'}
            </Text>
            <TouchableOpacity
              style={[styles.errorRetryBtn, blockedBySensitive && styles.errorRetryBtnBlock]}
              onPress={resetScan}
              activeOpacity={0.85}
            >
              <Ionicons name="camera-outline" size={18} color="#fff" />
              <Text style={styles.errorRetryText}>もう一度スキャン</Text>
            </TouchableOpacity>
          </View>
        </View>
      )}

      {/* Preview Sheet */}
      {showPreview && (
        <View style={StyleSheet.absoluteFill} pointerEvents="box-none">
          <TouchableOpacity
            style={styles.sheetOverlay}
            activeOpacity={1}
            onPress={handleCancel}
          />
          <Animated.View
            style={[
              styles.sheet,
              { paddingBottom: insets.bottom + Spacing.lg },
              { transform: [{ translateY: slideAnim }] },
            ]}
          >
            {/* Handle */}
            <View style={styles.sheetHandle} />

            <View style={styles.sheetHeader}>
              <View style={styles.sheetIconWrap}>
                <Ionicons name="checkmark-circle" size={22} color={AppColors.success} />
              </View>
              <Text style={styles.sheetTitle}>QRコードを読み取りました</Text>
            </View>

            <View style={styles.sheetDivider} />

            <ScrollView
              style={styles.sheetScroll}
              contentContainerStyle={styles.sheetContent}
              showsVerticalScrollIndicator={false}
            >
              {/* Category badge */}
              {previewMemo && (() => {
                const cfg = getCategoryConfig(previewMemo.category);
                return (
                  <View style={styles.previewField}>
                    <Text style={styles.previewLabel}>カテゴリー</Text>
                    <View style={[styles.previewCatBadge, { backgroundColor: cfg.bgColor }]}>
                      <Ionicons name={cfg.icon as any} size={13} color={cfg.color} />
                      <Text style={[styles.previewCatLabel, { color: cfg.color }]}>{cfg.label}</Text>
                    </View>
                  </View>
                );
              })()}
              <View style={styles.sheetDivider} />
              <View style={styles.previewField}>
                <Text style={styles.previewLabel}>タイトル</Text>
                {previewMemo && hasSensitiveContent(previewMemo.title, '') && !titleRevealed ? (
                  <SensitiveMask onReveal={() => setTitleRevealed(true)} compact />
                ) : (
                  <Text style={styles.previewValue}>{previewMemo?.title || '（無題）'}</Text>
                )}
              </View>
              <View style={styles.sheetDivider} />
              <View style={styles.previewField}>
                <Text style={styles.previewLabel}>内容</Text>
                {previewMemo && hasSensitiveContent('', previewMemo.content) && !contentRevealed ? (
                  <SensitiveMask onReveal={() => setContentRevealed(true)} />
                ) : (
                  <Text style={styles.previewContent}>{previewMemo?.content || '（内容なし）'}</Text>
                )}
              </View>
            </ScrollView>

            <View style={styles.sheetActions}>
              <TouchableOpacity style={styles.sheetCancelBtn} onPress={handleCancel} activeOpacity={0.8}>
                <Text style={styles.sheetCancelText}>キャンセル</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.sheetSaveBtn} onPress={handleSave} activeOpacity={0.85}>
                <Ionicons name="save-outline" size={18} color="#fff" />
                <Text style={styles.sheetSaveText}>保存する</Text>
              </TouchableOpacity>
            </View>
          </Animated.View>
        </View>
      )}
    </View>
  );
}

// ─── Sensitive Mask ───────────────────────────────────────────────────────────

function SensitiveMask({ onReveal, compact = false }: { onReveal: () => void; compact?: boolean }) {
  return (
    <TouchableOpacity style={[maskStyles.container, compact && maskStyles.containerCompact]} onPress={onReveal} activeOpacity={0.8}>
      {/* 擬似モザイク背景 */}
      <View style={maskStyles.mosaic}>
        {Array.from({ length: compact ? 3 : 6 }).map((_, row) => (
          <View key={row} style={maskStyles.mosaicRow}>
            {Array.from({ length: 12 }).map((_, col) => (
              <View
                key={col}
                style={[
                  maskStyles.mosaicCell,
                  { backgroundColor: (row + col) % 2 === 0 ? 'rgba(180,180,190,0.35)' : 'rgba(210,210,220,0.25)' },
                ]}
              />
            ))}
          </View>
        ))}
      </View>
      {/* オーバーレイ */}
      <View style={maskStyles.overlay}>
        <Ionicons name="eye-off-outline" size={compact ? 16 : 22} color={AppColors.textSecondary} />
        <Text style={[maskStyles.label, compact && maskStyles.labelCompact]}>センシティブなコンテンツ</Text>
        <View style={maskStyles.revealBtn}>
          <Ionicons name="eye-outline" size={13} color={AppColors.primary} />
          <Text style={maskStyles.revealBtnText}>タップして表示</Text>
        </View>
      </View>
    </TouchableOpacity>
  );
}

const maskStyles = StyleSheet.create({
  container: {
    borderRadius: Radius.md,
    overflow: 'hidden',
    minHeight: 90,
    backgroundColor: AppColors.divider,
  },
  containerCompact: { minHeight: 44 },
  mosaic: { position: 'absolute', inset: 0, flexDirection: 'column' },
  mosaicRow: { flex: 1, flexDirection: 'row' },
  mosaicCell: { flex: 1 },
  overlay: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    paddingVertical: Spacing.md,
    backgroundColor: 'rgba(248,248,252,0.82)',
  },
  label: { fontSize: 13, fontWeight: '600', color: AppColors.textSecondary },
  labelCompact: { fontSize: 12 },
  revealBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: AppColors.primaryLight,
    paddingHorizontal: Spacing.md,
    paddingVertical: 4,
    borderRadius: Radius.full,
  },
  revealBtnText: { fontSize: 12, fontWeight: '700', color: AppColors.primary },
});

// ─── Styles ───────────────────────────────────────────────────────────────────

const OVERLAY_COLOR = 'rgba(10, 10, 20, 0.62)';
const BRACKET_SIZE = 24;
const BRACKET_THICKNESS = 3;

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#000',
  },

  // Permission screen
  permContainer: {
    flex: 1,
    backgroundColor: AppColors.bg,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: Spacing.xl,
    gap: Spacing.md,
  },
  permIcon: {
    width: 96,
    height: 96,
    borderRadius: Radius.xxl,
    backgroundColor: AppColors.primaryLight,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: Spacing.sm,
  },
  permTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: AppColors.textPrimary,
    textAlign: 'center',
  },
  permText: {
    fontSize: 14,
    color: AppColors.textSecondary,
    textAlign: 'center',
    lineHeight: 22,
  },
  permBtn: {
    backgroundColor: AppColors.primary,
    paddingHorizontal: Spacing.xl,
    paddingVertical: Spacing.md,
    borderRadius: Radius.full,
    marginTop: Spacing.sm,
    ...Shadow.fab,
  },
  permBtnText: {
    fontSize: 15,
    fontWeight: '700',
    color: '#fff',
  },
  permCancelBtn: {
    paddingVertical: Spacing.sm,
  },
  permCancelText: {
    fontSize: 14,
    color: AppColors.textMuted,
  },

  // Camera overlay
  overlayTop: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: FRAME_CENTER_Y - FRAME_SIZE / 2,
    backgroundColor: OVERLAY_COLOR,
  },
  overlayMiddle: {
    position: 'absolute',
    top: FRAME_CENTER_Y - FRAME_SIZE / 2,
    left: 0,
    right: 0,
    height: FRAME_SIZE,
    flexDirection: 'row',
  },
  overlaySide: {
    flex: 1,
    backgroundColor: OVERLAY_COLOR,
  },
  scanFrame: {
    width: FRAME_SIZE,
    height: FRAME_SIZE,
    position: 'relative',
  },
  overlayBottom: {
    position: 'absolute',
    top: FRAME_CENTER_Y + FRAME_SIZE / 2,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: OVERLAY_COLOR,
  },

  // Corner brackets
  bracket: {
    position: 'absolute',
    width: BRACKET_SIZE,
    height: BRACKET_SIZE,
    borderColor: '#fff',
    borderWidth: BRACKET_THICKNESS,
  },
  bracketTL: {
    top: 0,
    left: 0,
    borderRightWidth: 0,
    borderBottomWidth: 0,
    borderTopLeftRadius: 4,
  },
  bracketTR: {
    top: 0,
    right: 0,
    borderLeftWidth: 0,
    borderBottomWidth: 0,
    borderTopRightRadius: 4,
  },
  bracketBL: {
    bottom: 0,
    left: 0,
    borderRightWidth: 0,
    borderTopWidth: 0,
    borderBottomLeftRadius: 4,
  },
  bracketBR: {
    bottom: 0,
    right: 0,
    borderLeftWidth: 0,
    borderTopWidth: 0,
    borderBottomRightRadius: 4,
  },

  // Top bar
  topBar: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: Spacing.lg,
    paddingBottom: Spacing.md,
  },
  closeBtn: {
    width: 44,
    height: 44,
    borderRadius: Radius.full,
    backgroundColor: 'rgba(255,255,255,0.15)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  topTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: '#fff',
    textShadowColor: 'rgba(0,0,0,0.4)',
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 3,
  },

  // Hint
  hintBar: {
    position: 'absolute',
    bottom: 60,
    left: 0,
    right: 0,
    alignItems: 'center',
    gap: Spacing.sm,
    paddingHorizontal: Spacing.xl,
  },
  hintText: {
    fontSize: 13,
    color: 'rgba(255,255,255,0.8)',
    textAlign: 'center',
    textShadowColor: 'rgba(0,0,0,0.5)',
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 3,
  },
  retryBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.xs,
    backgroundColor: 'rgba(255,255,255,0.2)',
    paddingHorizontal: Spacing.md,
    paddingVertical: Spacing.xs + 2,
    borderRadius: Radius.full,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.4)',
  },
  retryBtnDisabled: { opacity: 0.5 },
  restartingOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: '#000',
    alignItems: 'center',
    justifyContent: 'center',
    gap: Spacing.md,
  },
  restartingText: {
    fontSize: 15,
    color: 'rgba(255,255,255,0.7)',
    fontWeight: '500',
  },
  retryBtnText: {
    fontSize: 13,
    color: '#fff',
    fontWeight: '600',
  },

  // Error overlay
  errorOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.82)',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: Spacing.xl,
  },
  errorCard: {
    width: '100%',
    backgroundColor: '#1C1C1E',
    borderRadius: Radius.xxl,
    alignItems: 'center',
    padding: Spacing.xl,
    gap: Spacing.sm,
  },
  errorIconWrap: {
    width: 72,
    height: 72,
    borderRadius: Radius.xxl,
    backgroundColor: 'rgba(239,68,68,0.15)',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: Spacing.xs,
  },
  errorTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: '#fff',
    letterSpacing: -0.3,
  },
  errorSub: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.55)',
    textAlign: 'center',
    lineHeight: 21,
    marginBottom: Spacing.sm,
  },
  errorRetryBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.xs,
    backgroundColor: AppColors.primary,
    paddingHorizontal: Spacing.xl,
    paddingVertical: Spacing.sm + 4,
    borderRadius: Radius.full,
    ...Shadow.fab,
  },
  errorIconWrapBlock: { backgroundColor: 'rgba(245,158,11,0.15)' },
  errorRetryBtnBlock: { backgroundColor: '#F59E0B' },
  errorRetryText: {
    fontSize: 15,
    fontWeight: '700',
    color: '#fff',
  },

  // Preview Sheet
  sheetOverlay: {
    flex: 1,
  },
  sheet: {
    backgroundColor: AppColors.bgCard,
    borderTopLeftRadius: Radius.xxl,
    borderTopRightRadius: Radius.xxl,
    paddingTop: Spacing.sm,
    ...Shadow.modal,
    maxHeight: '70%',
  },
  sheetHandle: {
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: AppColors.border,
    alignSelf: 'center',
    marginBottom: Spacing.md,
  },
  sheetHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.sm,
    paddingHorizontal: Spacing.lg,
    paddingBottom: Spacing.md,
  },
  sheetIconWrap: {
    width: 32,
    height: 32,
    borderRadius: Radius.md,
    backgroundColor: AppColors.successLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sheetTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: AppColors.textPrimary,
  },
  sheetDivider: {
    height: 1,
    backgroundColor: AppColors.divider,
    marginHorizontal: Spacing.lg,
  },
  sheetScroll: {
    maxHeight: 240,
  },
  sheetContent: {
    paddingVertical: Spacing.sm,
  },
  previewField: {
    paddingHorizontal: Spacing.lg,
    paddingVertical: Spacing.md,
    gap: Spacing.xs,
  },
  previewLabel: {
    fontSize: 11,
    fontWeight: '700',
    color: AppColors.primary,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  previewValue: {
    fontSize: 17,
    fontWeight: '600',
    color: AppColors.textPrimary,
  },
  previewContent: {
    fontSize: 14,
    color: AppColors.textSecondary,
    lineHeight: 22,
  },
  previewCatBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.xs,
    paddingHorizontal: Spacing.sm,
    paddingVertical: Spacing.xs,
    borderRadius: Radius.sm,
    alignSelf: 'flex-start',
  },
  previewCatEmoji: { fontSize: 14 },
  previewCatLabel: { fontSize: 13, fontWeight: '700' },
  sheetActions: {
    flexDirection: 'row',
    gap: Spacing.sm,
    paddingHorizontal: Spacing.lg,
    paddingTop: Spacing.md,
  },
  sheetCancelBtn: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: Spacing.sm + 4,
    borderRadius: Radius.lg,
    borderWidth: 1.5,
    borderColor: AppColors.border,
  },
  sheetCancelText: {
    fontSize: 15,
    fontWeight: '600',
    color: AppColors.textSecondary,
  },
  sheetSaveBtn: {
    flex: 2,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: Spacing.xs,
    backgroundColor: AppColors.primary,
    paddingVertical: Spacing.sm + 4,
    borderRadius: Radius.lg,
    ...Shadow.fab,
  },
  sheetSaveText: {
    fontSize: 15,
    fontWeight: '700',
    color: '#fff',
  },
});
