import React, { useEffect, useRef, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ScrollView,
  Alert,
  Share,
  ActivityIndicator,
} from 'react-native';
import { useLocalSearchParams, router } from 'expo-router';
import QRCode from 'react-native-qrcode-svg';
import Svg, {
  Rect,
  Image as SvgImage,
  Text as SvgText,
  Circle,
  Line,
  Defs,
  LinearGradient,
  Stop,
} from 'react-native-svg';
import { Ionicons } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import * as MediaLibrary from 'expo-media-library';
import { File, Paths } from 'expo-file-system';
import * as Haptics from 'expo-haptics';

import { getMemoById } from '@/lib/storage';
import { Memo } from '@/lib/types';
import { encodeQR, formatDateTime } from '@/lib/qr';
import { getCategoryConfig } from '@/lib/categories';
import { hasSensitiveContent } from '@/lib/filter';
import { AppColors, Spacing, Radius, Shadow } from '@/constants/theme';
import { useSettings } from '@/lib/settingsContext';

// ─── 保存画像のカード寸法 ─────────────────────────────────────────────────────
const CARD_W = 360;
const QR_SIZE = 200;
const CARD_PAD = 28;
const META_TOP = CARD_PAD + QR_SIZE + CARD_PAD;   // separator Y
const CARD_H = META_TOP + 110;                      // 全高

export default function QRShowScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const insets = useSafeAreaInsets();
  const { colors, isDark } = useSettings();

  const [memo, setMemo] = useState<Memo | null>(null);
  const [qrValue, setQRValue] = useState(' ');
  const [saving, setSaving] = useState(false);
  const [titleRevealed, setTitleRevealed] = useState(false);
  const [captureBase64, setCaptureBase64] = useState<string | null>(null);
  const qrRef = useRef<any>(null);
  const compositeRef = useRef<any>(null);

  useEffect(() => { loadMemo(); }, [id]);

  async function loadMemo() {
    if (!id) return;
    const data = await getMemoById(id);
    if (data) {
      setMemo(data);
      setQRValue(encodeQR(data));
    }
  }

  // 保存処理 ─ Step1: QR PNG取得 → Step2: 合成SVGをキャプチャ → 保存
  async function handleSaveImage() {
    if (!qrRef.current || !memo) return;
    setSaving(true);

    const { status } = await MediaLibrary.requestPermissionsAsync();
    if (status !== 'granted') {
      Alert.alert('権限が必要です', '写真ライブラリへのアクセスを許可してください');
      setSaving(false);
      return;
    }

    qrRef.current.toDataURL((qrBase64: string) => {
      setCaptureBase64(qrBase64);
      // SVGが再レンダリングされてからキャプチャ
      setTimeout(() => {
        if (!compositeRef.current) { setSaving(false); return; }
        compositeRef.current.toDataURL(async (compositeBase64: string) => {
          try {
            const binary = atob(compositeBase64);
            const bytes = new Uint8Array(binary.length);
            for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
            const file = new File(Paths.cache, `nemoa_${Date.now()}.png`);
            file.write(bytes);
            await MediaLibrary.saveToLibraryAsync(file.uri);
            file.delete();
            await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
            Alert.alert('保存完了', 'QRコードを写真に保存しました');
          } catch {
            Alert.alert('エラー', '保存に失敗しました');
          } finally {
            setSaving(false);
            setCaptureBase64(null);
          }
        });
      }, 200);
    });
  }

  async function handleShare() {
    if (!memo) return;
    await Share.share({
      message: `【${memo.title || '無題'}】\n${memo.content}`,
      title: memo.title || 'nemoa',
    });
  }

  if (!memo) {
    return (
      <View style={[styles.container, styles.loading, { paddingTop: insets.top, backgroundColor: colors.bg }]}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  const cfg = getCategoryConfig(memo.category);
  const isSensitiveTitle = hasSensitiveContent(memo.title, '');
  const chipBg = isDark ? cfg.color + '22' : cfg.bgColor;
  const qrFg = isDark ? '#E0E7FF' : AppColors.qrFg;
  const qrBg = isDark ? colors.bgCard : '#FFFFFF';

  // 合成カードに表示するタイトル（センシティブは伏せ字）
  const saveTitle = isSensitiveTitle ? '● ● ● センシティブ ● ● ●' : (memo.title || '無題');
  const truncTitle = saveTitle.length > 22 ? saveTitle.slice(0, 21) + '…' : saveTitle;

  return (
    <View style={[styles.container, { paddingTop: insets.top, backgroundColor: colors.bg }]}>
      {/* Header */}
      <View style={[styles.header, { backgroundColor: colors.bgCard, borderBottomColor: colors.divider }]}>
        <TouchableOpacity style={styles.headerBtn} onPress={() => router.back()}>
          <Ionicons name="chevron-back" size={22} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={[styles.headerTitle, { color: colors.textPrimary }]}>QRコード</Text>
        <TouchableOpacity style={styles.headerBtn} onPress={handleShare}>
          <Ionicons name="share-outline" size={20} color={colors.primary} />
        </TouchableOpacity>
      </View>

      <ScrollView
        contentContainerStyle={[styles.content, { paddingBottom: insets.bottom + 40 }]}
        showsVerticalScrollIndicator={false}
      >
        {/* ── QR Card（画面表示用） ── */}
        <View style={[styles.qrCard, { backgroundColor: colors.bgCard }]}>
          <View style={styles.qrLabelRow}>
            <View style={[styles.qrLabelLine, { backgroundColor: colors.primaryMid }]} />
            <Text style={[styles.qrLabelText, { color: colors.primary }]}>スキャンして共有</Text>
            <View style={[styles.qrLabelLine, { backgroundColor: colors.primaryMid }]} />
          </View>

          <View style={[styles.qrWrapper, { backgroundColor: qrBg, borderColor: colors.border }]}>
            <QRCode
              value={qrValue}
              size={170}
              color={qrFg}
              backgroundColor={qrBg}
              getRef={(ref) => { qrRef.current = ref; }}
              quietZone={10}
            />
          </View>

          {/* カテゴリ・タイトル（画面表示用） */}
          <View style={[styles.qrMeta, { borderTopColor: colors.divider }]}>
            <View style={[styles.qrCatBadge, { backgroundColor: chipBg }]}>
              <View style={[styles.qrCatDot, { backgroundColor: cfg.color }]} />
              <Text style={[styles.qrCatLabel, { color: cfg.color }]}>{cfg.label}</Text>
            </View>

            {isSensitiveTitle && !titleRevealed ? (
              <TouchableOpacity style={styles.qrTitleSensitive} onPress={() => setTitleRevealed(true)}>
                <Ionicons name="eye-off-outline" size={13} color={colors.textMuted} />
                <Text style={[styles.qrTitleSensitiveText, { color: colors.textMuted }]}>
                  タップしてタイトルを表示
                </Text>
              </TouchableOpacity>
            ) : (
              <Text style={[styles.qrTitle, { color: colors.textPrimary }]} numberOfLines={2}>
                {memo.title || '（無題）'}
              </Text>
            )}
          </View>

          <View style={[styles.corner, styles.cornerTL, { borderColor: colors.primary }]} />
          <View style={[styles.corner, styles.cornerTR, { borderColor: colors.primary }]} />
          <View style={[styles.corner, styles.cornerBL, { borderColor: colors.primary }]} />
          <View style={[styles.corner, styles.cornerBR, { borderColor: colors.primary }]} />
        </View>

        {/* 保存ボタン */}
        <TouchableOpacity
          style={[styles.saveBtn, saving && styles.saveBtnLoading]}
          onPress={handleSaveImage}
          activeOpacity={0.8}
          disabled={saving}
        >
          {saving ? (
            <ActivityIndicator size="small" color="#fff" />
          ) : (
            <Ionicons name="download-outline" size={18} color="#fff" />
          )}
          <Text style={styles.saveBtnText}>{saving ? '保存中...' : '写真に保存'}</Text>
        </TouchableOpacity>

        {/* メモ情報カード */}
        <View style={[styles.infoCard, { backgroundColor: colors.bgCard }]}>
          <View style={styles.infoHeader}>
            <View style={[styles.infoIconWrap, { backgroundColor: colors.primaryLight }]}>
              <Ionicons name="document-text-outline" size={15} color={colors.primary} />
            </View>
            <Text style={[styles.infoHeaderText, { color: colors.textPrimary }]}>メモの内容</Text>
          </View>
          <View style={[styles.infoDivider, { backgroundColor: colors.divider }]} />
          <View style={styles.infoSection}>
            <Text style={[styles.infoLabel, { color: colors.primary }]}>タイトル</Text>
            <Text style={[styles.infoValue, { color: colors.textPrimary }]}>{memo.title || '（無題）'}</Text>
          </View>
          <View style={[styles.infoDivider, { backgroundColor: colors.divider }]} />
          <View style={styles.infoSection}>
            <Text style={[styles.infoLabel, { color: colors.primary }]}>内容</Text>
            <Text style={[styles.infoContent, { color: colors.textSecondary }]}>{memo.content || '（内容なし）'}</Text>
          </View>
          <View style={[styles.infoDivider, { backgroundColor: colors.divider }]} />
          <View style={styles.infoMeta}>
            <Ionicons name="time-outline" size={12} color={colors.textMuted} />
            <Text style={[styles.infoMetaText, { color: colors.textMuted }]}>更新: {formatDateTime(memo.updatedAt)}</Text>
          </View>
        </View>

        <View style={styles.hint}>
          <Ionicons name="information-circle-outline" size={14} color={colors.textMuted} />
          <Text style={[styles.hintText, { color: colors.textMuted }]}>
            nemoaでスキャンするとメモとして保存できます
          </Text>
        </View>
      </ScrollView>

      {/* ── 保存用合成SVG（画面外・不可視） ── */}
      {captureBase64 && (
        <View style={styles.hiddenCapture} pointerEvents="none">
          <Svg ref={compositeRef} width={CARD_W} height={CARD_H}>
            <Defs>
              <LinearGradient id="cardBg" x1="0" y1="0" x2="0" y2="1">
                <Stop offset="0" stopColor="#FFFFFF" stopOpacity="1" />
                <Stop offset="1" stopColor="#F8FAFF" stopOpacity="1" />
              </LinearGradient>
            </Defs>

            {/* カード背景 */}
            <Rect width={CARD_W} height={CARD_H} fill="url(#cardBg)" rx={24} />

            {/* 上部アクセントライン */}
            <Rect x={CARD_W / 2 - 24} y={14} width={48} height={3} fill={cfg.color} rx={2} />

            {/* QRコード */}
            <SvgImage
              href={`data:image/png;base64,${captureBase64}`}
              x={(CARD_W - QR_SIZE) / 2}
              y={CARD_PAD}
              width={QR_SIZE}
              height={QR_SIZE}
            />

            {/* セパレーター */}
            <Line
              x1={32} y1={META_TOP} x2={CARD_W - 32} y2={META_TOP}
              stroke="#E2E8F0" strokeWidth={1}
            />

            {/* カテゴリバッジ背景 */}
            <Rect
              x={CARD_W / 2 - 56} y={META_TOP + 16}
              width={112} height={26}
              fill={cfg.color + '18'} rx={13}
            />

            {/* カテゴリドット */}
            <Circle cx={CARD_W / 2 - 32} cy={META_TOP + 29} r={5} fill={cfg.color} />

            {/* カテゴリラベル */}
            <SvgText
              x={CARD_W / 2 - 22} y={META_TOP + 34}
              fill={cfg.color}
              fontSize={13}
              fontWeight="700"
            >
              {cfg.label}
            </SvgText>

            {/* タイトル */}
            <SvgText
              x={CARD_W / 2} y={META_TOP + 72}
              textAnchor="middle"
              fill="#0F172A"
              fontSize={18}
              fontWeight="700"
            >
              {truncTitle}
            </SvgText>

            {/* nemoa ブランド */}
            <SvgText
              x={CARD_W / 2} y={CARD_H - 14}
              textAnchor="middle"
              fill="#94A3B8"
              fontSize={11}
            >
              nemoa
            </SvgText>
          </Svg>
        </View>
      )}
    </View>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const styles = StyleSheet.create({
  container: { flex: 1 },
  loading: { alignItems: 'center', justifyContent: 'center' },
  hiddenCapture: { position: 'absolute', left: -9999, top: -9999 },

  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: Spacing.md, paddingVertical: Spacing.sm + 4, borderBottomWidth: 1,
  },
  headerBtn: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center', borderRadius: Radius.md },
  headerTitle: { fontSize: 16, fontWeight: '700' },

  content: { paddingHorizontal: Spacing.lg, paddingTop: Spacing.xl, gap: Spacing.lg, alignItems: 'center' },

  // QR Card
  qrCard: { borderRadius: Radius.xl, padding: Spacing.xl, alignItems: 'center', width: '100%', position: 'relative', ...Shadow.card },
  qrLabelRow: { flexDirection: 'row', alignItems: 'center', gap: Spacing.sm, marginBottom: Spacing.lg },
  qrLabelLine: { width: 24, height: 1.5, borderRadius: 1 },
  qrLabelText: { fontSize: 11, fontWeight: '700', letterSpacing: 1, textTransform: 'uppercase' },
  qrWrapper: { padding: Spacing.md, borderRadius: Radius.lg, borderWidth: 1 },

  qrMeta: { width: '100%', alignItems: 'center', gap: Spacing.sm, marginTop: Spacing.lg, paddingTop: Spacing.lg, borderTopWidth: 1 },
  qrCatBadge: { flexDirection: 'row', alignItems: 'center', gap: 5, paddingHorizontal: Spacing.sm + 2, paddingVertical: 4, borderRadius: Radius.full },
  qrCatDot: { width: 6, height: 6, borderRadius: 3 },
  qrCatLabel: { fontSize: 12, fontWeight: '700' },
  qrTitle: { fontSize: 17, fontWeight: '700', letterSpacing: -0.2, textAlign: 'center' },
  qrTitleSensitive: { flexDirection: 'row', alignItems: 'center', gap: 5 },
  qrTitleSensitiveText: { fontSize: 13, fontStyle: 'italic' },

  corner: { position: 'absolute', width: 18, height: 18, borderWidth: 2.5 },
  cornerTL: { top: 14, left: 14, borderRightWidth: 0, borderBottomWidth: 0, borderTopLeftRadius: 5 },
  cornerTR: { top: 14, right: 14, borderLeftWidth: 0, borderBottomWidth: 0, borderTopRightRadius: 5 },
  cornerBL: { bottom: 14, left: 14, borderRightWidth: 0, borderTopWidth: 0, borderBottomLeftRadius: 5 },
  cornerBR: { bottom: 14, right: 14, borderLeftWidth: 0, borderTopWidth: 0, borderBottomRightRadius: 5 },

  // Save button
  saveBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: Spacing.xs, backgroundColor: AppColors.primary, paddingHorizontal: Spacing.xl, paddingVertical: Spacing.sm + 4, borderRadius: Radius.full, width: '100%', ...Shadow.fab },
  saveBtnLoading: { opacity: 0.7 },
  saveBtnText: { fontSize: 15, fontWeight: '700', color: '#fff' },

  // Info card
  infoCard: { borderRadius: Radius.lg, width: '100%', overflow: 'hidden', ...Shadow.card },
  infoHeader: { flexDirection: 'row', alignItems: 'center', gap: Spacing.sm, padding: Spacing.md },
  infoIconWrap: { width: 28, height: 28, borderRadius: Radius.sm, alignItems: 'center', justifyContent: 'center' },
  infoHeaderText: { fontSize: 14, fontWeight: '700' },
  infoDivider: { height: 1 },
  infoSection: { padding: Spacing.md, gap: Spacing.xs },
  infoLabel: { fontSize: 11, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5 },
  infoValue: { fontSize: 16, fontWeight: '600' },
  infoContent: { fontSize: 14, lineHeight: 22 },
  infoMeta: { flexDirection: 'row', alignItems: 'center', gap: Spacing.xs, padding: Spacing.md },
  infoMetaText: { fontSize: 12 },

  // Hint
  hint: { flexDirection: 'row', alignItems: 'flex-start', gap: Spacing.xs, paddingHorizontal: Spacing.sm },
  hintText: { flex: 1, fontSize: 12, lineHeight: 18 },
});
