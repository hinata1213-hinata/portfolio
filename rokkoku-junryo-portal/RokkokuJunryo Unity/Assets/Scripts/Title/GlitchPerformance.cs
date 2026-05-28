    using UnityEngine;
using UnityEngine.UI;
using System.Collections;

public class GlitchPerformance : MonoBehaviour
{
    [Header("設定")]
    [SerializeField] private Image targetPanel; // 画像を表示するパネル
    [SerializeField] private Sprite[] flashbackImages; // 差し込む画像の配列
    
    [Header("演出パラメータ")]
    [SerializeField] private float minInterval = 0.05f; // 最小切り替え間隔
    [SerializeField] private float maxInterval = 0.3f; // 最大切り替え間隔
    [Tooltip("グリッチ演出の継続時間（0以下で永続）")]
    [SerializeField] private float glitchDuration = 2f; // グリッチ演出の継続時間（0以下で永続）
    [SerializeField] private bool playOnStart = true; // 開始時に自動再生
    
    [Header("グリッチエフェクト")]
    [SerializeField] private bool useColorGlitch = true; // 色のバグ演出
    [SerializeField] private bool useScaleGlitch = true; // スケールのバグ演出
    [SerializeField] private bool usePositionGlitch = true; // 位置のバグ演出
    
    [Header("BGM設定 🎵")]
    [SerializeField] private AudioSource bgmSource; // BGM再生用AudioSource
    [SerializeField] private AudioClip bgmClip; // 再生するBGM
    [SerializeField] private bool loopBGM = true; // BGMをループするか
    [SerializeField] private Slider volumeSlider; // 音量調整スライダー
    [SerializeField] private float bgmFadeDuration = 1f; // BGMフェードイン/アウトの時間
    
    private RectTransform panelRect;
    private Vector3 originalScale;
    private Vector3 originalPosition;
    private bool isPlaying = false;

    void Start()
    {
        if (targetPanel != null)
        {
            panelRect = targetPanel.GetComponent<RectTransform>();
            originalScale = panelRect.localScale;
            originalPosition = panelRect.localPosition;
        }
        
        // 🎚️ スライダー設定
        if (volumeSlider != null && bgmSource != null)
        {
            volumeSlider.onValueChanged.AddListener((value) =>
            {
                bgmSource.volume = value;
            });
            float savedVolume = PlayerPrefs.GetFloat("FlashbackBGMVolume", 0.5f);
            volumeSlider.value = savedVolume;
            bgmSource.volume = savedVolume;
        }
        
        if (playOnStart)
        {
            StartGlitchEffect();
        }
    }

    void OnDisable()
    {
        // 音量を保存
        if (volumeSlider != null)
        {
            PlayerPrefs.SetFloat("FlashbackBGMVolume", volumeSlider.value);
        }
        
        // BGMを停止
        if (bgmSource != null && bgmSource.isPlaying)
        {
            bgmSource.Stop();
        }
    }

    void Update()
    {
        // スペースキーで手動再生（テスト用）
        if (Input.GetKeyDown(KeyCode.Space) && !isPlaying)
        {
            StartGlitchEffect();
        }
        
        // Escキーで停止（永続モード用）
        if (Input.GetKeyDown(KeyCode.Escape) && isPlaying)
        {
            StopGlitchEffect();
        }
    }

    /// <summary>
    /// グリッチエフェクトを開始
    /// </summary>
    public void StartGlitchEffect()
    {
        if (targetPanel == null || flashbackImages == null || flashbackImages.Length == 0)
        {
            Debug.LogWarning("パネルまたは画像が設定されていません");
            return;
        }
        
        if (!isPlaying)
        {
            StartCoroutine(GlitchCoroutine());
            
            // 🎵 BGMを再生
            if (bgmSource != null && bgmClip != null)
            {
                StartCoroutine(PlayBGMWithFadeIn());
            }
        }
    }

    /// <summary>
    /// 🎵 BGMをフェードインで再生
    /// </summary>
    private IEnumerator PlayBGMWithFadeIn()
    {
        if (bgmSource.isPlaying)
        {
            yield break;
        }
        
        bgmSource.clip = bgmClip;
        bgmSource.loop = loopBGM;
        bgmSource.volume = 0f;
        bgmSource.Play();
        
        float targetVolume = (volumeSlider != null) ? volumeSlider.value : 0.5f;
        float elapsedTime = 0f;
        
        while (elapsedTime < bgmFadeDuration)
        {
            elapsedTime += Time.deltaTime;
            bgmSource.volume = Mathf.Lerp(0f, targetVolume, elapsedTime / bgmFadeDuration);
            yield return null;
        }
        
        bgmSource.volume = targetVolume;
    }

    /// <summary>
    /// 🎵 BGMをフェードアウトで停止
    /// </summary>
    private IEnumerator StopBGMWithFadeOut()
    {
        if (bgmSource == null || !bgmSource.isPlaying)
        {
            yield break;
        }
        
        float startVolume = bgmSource.volume;
        float elapsedTime = 0f;
        
        while (elapsedTime < bgmFadeDuration)
        {
            elapsedTime += Time.deltaTime;
            bgmSource.volume = Mathf.Lerp(startVolume, 0f, elapsedTime / bgmFadeDuration);
            yield return null;
        }
        
        bgmSource.Stop();
        bgmSource.volume = startVolume;
    }

    /// <summary>
    /// グリッチ演出のコルーチン
    /// </summary>
    private IEnumerator GlitchCoroutine()
    {
        isPlaying = true;
        targetPanel.gameObject.SetActive(true);
        
        float elapsedTime = 0f;
        bool isPermanent = glitchDuration <= 0; // 👻 0以下なら永続
        
        // 永続モードまたは設定時間内ループ
        while (isPermanent || elapsedTime < glitchDuration)
        {
            // ランダムな画像を選択
            int randomIndex = Random.Range(0, flashbackImages.Length);
            targetPanel.sprite = flashbackImages[randomIndex];
            
            // 色のグリッチ
            if (useColorGlitch)
            {
                Color glitchColor = new Color(
                    Random.Range(0.5f, 1f),
                    Random.Range(0.5f, 1f),
                    Random.Range(0.5f, 1f),
                    Random.Range(0.7f, 1f)
                );
                targetPanel.color = glitchColor;
            }
            
            // スケールのグリッチ
            if (useScaleGlitch)
            {
                float scaleMultiplier = Random.Range(0.9f, 1.15f);
                panelRect.localScale = originalScale * scaleMultiplier;
            }
            
            // 位置のグリッチ
            if (usePositionGlitch)
            {
                Vector3 offset = new Vector3(
                    Random.Range(-20f, 20f),
                    Random.Range(-20f, 20f),
                    0f
                );
                panelRect.localPosition = originalPosition + offset;
            }
            
            // ランダムな間隔で次の画像へ
            float waitTime = Random.Range(minInterval, maxInterval);
            yield return new WaitForSeconds(waitTime);
            
            if (!isPermanent)
            {
                elapsedTime += waitTime;
            }
        }
        
        // 元の状態に戻す（永続モードでは実行されない）
        ResetPanel();
        isPlaying = false;
        
        // 🎵 BGMをフェードアウト（永続モードでない場合）
        if (!isPermanent)
        {
            StartCoroutine(StopBGMWithFadeOut());
        }
    }

    /// <summary>
    /// パネルを元の状態に戻す
    /// </summary>
    private void ResetPanel()
    {
        if (panelRect != null)
        {
            panelRect.localScale = originalScale;
            panelRect.localPosition = originalPosition;
        }
        
        if (targetPanel != null)
        {
            targetPanel.color = Color.white;
            targetPanel.gameObject.SetActive(false);
        }
    }

    /// <summary>
    /// 演出を停止
    /// </summary>
    public void StopGlitchEffect()
    {
        StopAllCoroutines();
        ResetPanel();
        isPlaying = false;
        
        // 🎵 BGMをフェードアウトで停止
        StartCoroutine(StopBGMWithFadeOut());
    }
}