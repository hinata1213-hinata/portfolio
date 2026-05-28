using UnityEngine;
using UnityEngine.UI;
using UnityEngine.SceneManagement; // 追加
using System.Collections;

public class TextMove : MonoBehaviour
{
    // テキストと画像、BGMをまとめた構造体
    [System.Serializable]
    public class TextLine
    {
        [TextArea(2, 5)]
        public string text; // 表示するテキスト
        
        [Header("🖼️ 背景画像設定")]
        public GameObject[] backgroundPanels; // 複数の背景画像Panel
        public float animationInterval = 1.0f; // アニメーション切り替え間隔
        public float crossFadeDuration = 0f; // クロスフェードの時間
        public bool loopAnimation = true; // アニメーションをループするか
        public bool randomOrder = false; // ランダム順で表示するか
        public float imageFadeDuration = 0.5f; // この行の画像フェード時間
        
        [Header("🎵 BGM設定")]
        public AudioClip bgmClip; // このテキストで再生するBGM
        public bool loopBGM = true; // BGMをループするかどうか
        
        [Header("⚡ テキスト速度")]
        public float textSpeed = 0.05f; // この行のテキスト表示速度
        
        [Header("👻 グリッチ演出設定")]
        public bool useGlitchEffect = false; // グリッチ演出を使用するか
        public float glitchStartDelay = 0f; // テキスト表示開始からグリッチ開始までの遅延
        [Tooltip("グリッチ演出の継続時間（0以下で永続）")]
        public float glitchDuration = 0f; // グリッチ演出の継続時間（0以下で永続）
        public float glitchInterval = 0.1f; // グリッチ効果の更新間隔
        [Range(0f, 1f)]
        public float colorGlitchIntensity = 0.3f; // この行の色の変化の強度（0-1）
        
        // 後方互換性のためのプロパティ
        public GameObject backgroundPanel
        {
            get { return (backgroundPanels != null && backgroundPanels.Length > 0) ? backgroundPanels[0] : null; }
            set 
            { 
                if (backgroundPanels == null || backgroundPanels.Length == 0)
                    backgroundPanels = new GameObject[1];
                backgroundPanels[0] = value;
            }
        }
    }

    [Header("テキスト設定")]
    [SerializeField] private Text displayText;
    [SerializeField] private TextLine[] textLines = new TextLine[]
    {
        new TextLine { text = "これはサウンドノベル風のテキストです。", loopBGM = true, textSpeed = 0.05f, imageFadeDuration = 0.5f },
        new TextLine { text = "複数の文章を順番に表示できます。", loopBGM = true, textSpeed = 0.05f, imageFadeDuration = 0.5f },
        new TextLine { text = "クリックで次のテキストに進みます。", loopBGM = true, textSpeed = 0.05f, imageFadeDuration = 0.5f }
    };

    [Header("表示速度設定")]
    [SerializeField] private float defaultTextSpeed = 0.05f; // デフォルトのテキスト速度

    [Header("オプション設定")]
    [SerializeField] private bool skipWithClick = true;

    [Header("次へ進むマーカー設定")]
    [SerializeField] private bool useMarker = true; // ✨ マーカー表示ON/OFF
    [SerializeField] private float markerBlinkSpeed = 0.5f;
    [SerializeField] private int markerSizePercent = 65;

    [Header("BGM設定 🎵")]
    [SerializeField] private AudioSource bgmSource; // BGM再生用AudioSource
    [SerializeField] private Slider volumeSlider;   // 音量調整スライダー
    [SerializeField] private float bgmFadeDuration = 0f; // BGM切り替えのフェード時間

    [Header("次のTextMove設定：次のTextMove用")]
    [SerializeField] private TextMove nextTextMove;

    [Header("代替表示オブジェクト設定：選択肢用GameObject")]
    [SerializeField] private GameObject alternativeObject;

    [Header("🎬 シーン遷移設定")]
    [SerializeField] private bool useSceneTransition = false; // シーン遷移を使用するか
    [SerializeField] private string targetSceneName = ""; // 遷移先のシーン名
    [SerializeField] private float sceneTransitionDelay = 0f; // シーン遷移までの遅延時間

    [Header("🚫 テキスト進行をブロックするPanel設定")]
    [SerializeField] private GameObject[] blockingPanels; // 表示中はテキストを進めないPanel配列

    private string displayedText = "";
    private string currentText = "";
    private int currentIndex = 0;
    private int currentLineIndex = 0;
    private bool isDisplaying = false;
    private bool isComplete = false;
    private bool isMarkerVisible = false;
    private Coroutine blinkCoroutine;
    private Coroutine glitchCoroutine;
    private Coroutine animationCoroutine; // 🎬 背景アニメーション用
    private string markerText;
    private bool isActive = false;
    private GameObject currentActivePanel;
    private GameObject[] currentActivePanels; // 🎬 現在アクティブな複数Panel
    
    // グリッチ用の変数
    private Image glitchTargetImage;
    private Color glitchOriginalColor;
    private bool isGlitchPlaying = false;

    void Awake()
    {
        if (nextTextMove != null)
        {
            nextTextMove.gameObject.SetActive(false);
        }

        if (alternativeObject != null)
        {
            alternativeObject.SetActive(false);
        }

        foreach (TextLine textLine in textLines)
        {
            if (textLine.backgroundPanels != null)
            {
                foreach (GameObject panel in textLine.backgroundPanels)
                {
                    if (panel != null)
                    {
                        panel.SetActive(false);
                    }
                }
            }
        }
    }

    void Start()
    {
        if (displayText == null)
        {
            displayText = GetComponent<Text>();
        }

        if (displayText != null)
        {
            displayText.supportRichText = true;
            displayText.text = "";
        }

        // ✨ マーカーを▶に変更
        markerText = "<size=" + markerSizePercent + "%>▶</size>";

        // 🎚️ スライダー設定
        if (volumeSlider != null && bgmSource != null)
        {
            volumeSlider.onValueChanged.AddListener((value) =>
            {
                bgmSource.volume = value;
            });
            float savedVolume = PlayerPrefs.GetFloat("BGMVolume", 0.5f);
            volumeSlider.value = savedVolume;
            bgmSource.volume = savedVolume;
        }

        displayedText = "";

        if (gameObject.activeInHierarchy)
        {
            isActive = true;
            if (textLines.Length > 0)
            {
                StartTextDisplay();
            }
        }
    }

    void OnDisable()
    {
        if (volumeSlider != null)
            PlayerPrefs.SetFloat("BGMVolume", volumeSlider.value);
        
        // 🖼️ 非アクティブになる際に、すべての画像を確実に非表示にする
        HideAllBackgroundPanels();
        
        // グリッチ演出を停止
        StopGlitchEffect();
        
        // 🎬 アニメーションを停止
        StopBackgroundAnimation();
    }

    void Update()
    {
        if (!isActive) return;

        // 🚫 ブロッキングPanelがアクティブな場合は入力を無視
        if (IsAnyBlockingPanelActive())
        {
            return;
        }

        if (skipWithClick)
        {
            // ✨ マウスクリック、スペースキー、エンターキー、リターンキーで進める
            if (Input.GetMouseButtonDown(0) || Input.GetKeyDown(KeyCode.Space) || 
                Input.GetKeyDown(KeyCode.Return) || Input.GetKeyDown(KeyCode.KeypadEnter))
            {
                if (isDisplaying && !isComplete)
                {
                    SkipToEnd();
                }
                else if (isComplete)
                {
                    ShowNextText();
                }
            }
        }
    }

    /// <summary>
    /// ブロッキングPanelのいずれかがアクティブかチェック
    /// </summary>
    private bool IsAnyBlockingPanelActive()
    {
        if (blockingPanels == null || blockingPanels.Length == 0)
        {
            return false;
        }

        foreach (GameObject panel in blockingPanels)
        {
            if (panel != null && panel.activeInHierarchy)
            {
                return true;
            }
        }

        return false;
    }

    /// <summary>
    /// 🖼️ このTextMoveで使用しているすべての背景画像を非表示にする
    /// </summary>
    private void HideAllBackgroundPanels()
    {
        foreach (TextLine textLine in textLines)
        {
            if (textLine.backgroundPanels != null)
            {
                foreach (GameObject panel in textLine.backgroundPanels)
                {
                    if (panel != null && panel.activeSelf)
                    {
                        panel.SetActive(false);
                    }
                }
            }
        }
        
        // currentActivePanelもクリア
        currentActivePanel = null;
        currentActivePanels = null;
    }

    /// <summary>
    /// 🖼️ 現在のTextLineのimageFadeDurationを取得
    /// </summary>
    private float GetCurrentImageFadeDuration()
    {
        if (currentLineIndex < textLines.Length)
        {
            return textLines[currentLineIndex].imageFadeDuration;
        }
        return 0.5f; // フォールバック値
    }

    public void StartTextDisplay()
    {
        if (!isDisplaying && currentLineIndex < textLines.Length)
        {
            HideMarker();

            // 🎬 前のアニメーションを停止
            StopBackgroundAnimation();

            // 🖼️ 背景画像の切り替え処理
            GameObject[] targetPanels = textLines[currentLineIndex].backgroundPanels;
            
            if (targetPanels != null && targetPanels.Length > 0)
            {
                // 複数Panelがある場合はアニメーション開始
                if (targetPanels.Length > 1)
                {
                    StartBackgroundAnimation(textLines[currentLineIndex]);
                }
                else
                {
                    // 1つだけの場合は従来通り
                    GameObject targetPanel = targetPanels[0];
                    if (targetPanel != currentActivePanel)
                    {
                        if (targetPanel != null)
                        {
                            StartCoroutine(ChangeBackgroundPanel(targetPanel));
                        }
                    }
                }
            }
            else if (currentActivePanel != null)
            {
                // backgroundPanelsがnullまたは空の場合、現在の画像を非表示にする
                StartCoroutine(HideCurrentPanel());
            }

            // 🎵 BGM切り替え（画像と並行して即座に開始）
            PlayBGMForCurrentLine();

            // 👻 グリッチ演出の開始チェック
            if (textLines[currentLineIndex].useGlitchEffect && 
                textLines[currentLineIndex].backgroundPanels != null && 
                textLines[currentLineIndex].backgroundPanels.Length > 0)
            {
                StartCoroutine(StartGlitchWithDelay());
            }

            StartCoroutine(DisplayTextCoroutine());
        }
    }

    /// <summary>
    /// 🎬 背景アニメーションを開始
    /// </summary>
    private void StartBackgroundAnimation(TextLine textLine)
    {
        if (animationCoroutine != null)
        {
            StopCoroutine(animationCoroutine);
        }
        
        // 現在のPanelを非表示
        if (currentActivePanel != null)
        {
            StartCoroutine(HideCurrentPanel());
        }
        
        currentActivePanels = textLine.backgroundPanels;
        animationCoroutine = StartCoroutine(BackgroundAnimationCoroutine(textLine));
    }

    /// <summary>
    /// 🎬 背景アニメーションを停止
    /// </summary>
    private void StopBackgroundAnimation()
    {
        if (animationCoroutine != null)
        {
            StopCoroutine(animationCoroutine);
            animationCoroutine = null;
        }
        
        // アニメーション中のすべてのPanelを非表示
        if (currentActivePanels != null)
        {
            foreach (GameObject panel in currentActivePanels)
            {
                if (panel != null)
                {
                    panel.SetActive(false);
                    // CanvasGroupのalphaもリセット
                    CanvasGroup cg = panel.GetComponent<CanvasGroup>();
                    if (cg != null)
                    {
                        cg.alpha = 1f;
                    }
                }
            }
            currentActivePanels = null;
        }
    }

    /// <summary>
    /// 🎬 背景アニメーションのコルーチン
    /// </summary>
    private IEnumerator BackgroundAnimationCoroutine(TextLine textLine)
    {
        GameObject[] panels = textLine.backgroundPanels;
        if (panels == null || panels.Length == 0) yield break;
        
        // 各PanelにCanvasGroupを追加（なければ）
        CanvasGroup[] canvasGroups = new CanvasGroup[panels.Length];
        for (int i = 0; i < panels.Length; i++)
        {
            if (panels[i] != null)
            {
                canvasGroups[i] = panels[i].GetComponent<CanvasGroup>();
                if (canvasGroups[i] == null)
                {
                    canvasGroups[i] = panels[i].AddComponent<CanvasGroup>();
                }
                canvasGroups[i].alpha = 0f;
                panels[i].SetActive(true);
            }
        }
        
        int currentPanelIndex = 0;
        int[] order = new int[panels.Length];
        
        // 順序を初期化
        for (int i = 0; i < panels.Length; i++)
        {
            order[i] = i;
        }
        
        // ランダム順の場合はシャッフル
        if (textLine.randomOrder)
        {
            ShuffleArray(order);
        }
        
        // 最初のPanelをフェードイン
        if (panels[order[0]] != null)
        {
            yield return StartCoroutine(FadePanel(canvasGroups[order[0]], 0f, 1f, textLine.crossFadeDuration));
            currentActivePanel = panels[order[0]];
        }
        
        // テキスト表示中ずっとアニメーションを続ける
        while (true)
        {
            // 次のPanelに切り替えるまで待機
            yield return new WaitForSeconds(textLine.animationInterval);
            
            int nextPanelIndex = (currentPanelIndex + 1) % panels.Length;
            
            // ループしない設定で最後まで行った場合は停止
            if (!textLine.loopAnimation && nextPanelIndex == 0)
            {
                yield break;
            }
            
            // ランダム順で最初に戻る場合は再シャッフル
            if (textLine.randomOrder && nextPanelIndex == 0)
            {
                ShuffleArray(order);
            }
            
            int currentIdx = order[currentPanelIndex];
            int nextIdx = order[nextPanelIndex];
            
            // クロスフェード
            if (panels[currentIdx] != null && panels[nextIdx] != null)
            {
                yield return StartCoroutine(CrossFadePanels(
                    canvasGroups[currentIdx], 
                    canvasGroups[nextIdx], 
                    textLine.crossFadeDuration
                ));
                currentActivePanel = panels[nextIdx];
            }
            
            currentPanelIndex = nextPanelIndex;
        }
    }

    /// <summary>
    /// 🎬 配列をシャッフル
    /// </summary>
    private void ShuffleArray(int[] array)
    {
        for (int i = array.Length - 1; i > 0; i--)
        {
            int j = Random.Range(0, i + 1);
            int temp = array[i];
            array[i] = array[j];
            array[j] = temp;
        }
    }

    /// <summary>
    /// 🎬 単一Panelのフェード
    /// </summary>
    private IEnumerator FadePanel(CanvasGroup canvasGroup, float from, float to, float duration)
    {
        if (canvasGroup == null) yield break;
        
        float elapsed = 0f;
        while (elapsed < duration)
        {
            elapsed += Time.deltaTime;
            canvasGroup.alpha = Mathf.Lerp(from, to, elapsed / duration);
            yield return null;
        }
        canvasGroup.alpha = to;
    }

    /// <summary>
    /// 🎬 2つのPanelをクロスフェード
    /// </summary>
    private IEnumerator CrossFadePanels(CanvasGroup fromCG, CanvasGroup toCG, float duration)
    {
        if (fromCG == null || toCG == null) yield break;
        
        float elapsed = 0f;
        while (elapsed < duration)
        {
            elapsed += Time.deltaTime;
            float t = elapsed / duration;
            fromCG.alpha = Mathf.Lerp(1f, 0f, t);
            toCG.alpha = Mathf.Lerp(0f, 1f, t);
            yield return null;
        }
        fromCG.alpha = 0f;
        toCG.alpha = 1f;
    }

    /// <summary>
    /// 👻 遅延後にグリッチ演出を開始
    /// </summary>
    private IEnumerator StartGlitchWithDelay()
    {
        TextLine currentLine = textLines[currentLineIndex];
        
        if (currentLine.glitchStartDelay > 0)
        {
            yield return new WaitForSeconds(currentLine.glitchStartDelay);
        }
        
        // backgroundPanelsの最初のPanelまたはその子オブジェクトからImageコンポーネントを取得
        GameObject targetPanel = currentLine.backgroundPanel;
        if (targetPanel != null)
        {
            // まず親のImageを確認
            Image targetImage = targetPanel.GetComponent<Image>();
            
            // 親にImageがない場合、子オブジェクトから検索
            if (targetImage == null)
            {
                targetImage = targetPanel.GetComponentInChildren<Image>();
            }
            
            if (targetImage != null)
            {
                StartGlitchEffect(currentLine, targetImage);
            }
            else
            {
                Debug.LogWarning("backgroundPanelまたはその子オブジェクトにImageコンポーネントがアタッチされていません: " + targetPanel.name);
            }
        }
    }

    /// <summary>
    /// 👻 グリッチ演出を開始
    /// </summary>
    private void StartGlitchEffect(TextLine textLine, Image targetImage)
    {
        if (isGlitchPlaying)
        {
            StopGlitchEffect();
        }
        
        // グリッチ対象のコンポーネントを取得
        glitchTargetImage = targetImage;
        
        if (glitchTargetImage != null)
        {
            // 元の状態を保存
            glitchOriginalColor = glitchTargetImage.color;
            
            glitchCoroutine = StartCoroutine(GlitchCoroutine(textLine));
        }
    }

    /// <summary>
    /// 👻 グリッチ演出のコルーチン（色の変化のみ）
    /// </summary>
    private IEnumerator GlitchCoroutine(TextLine textLine)
    {
        isGlitchPlaying = true;
        
        float elapsedTime = 0f;
        bool isPermanent = textLine.glitchDuration <= 0; // 👻 0以下なら永続
        
        // 永続モードまたは設定時間内ループ
        while (isPermanent || elapsedTime < textLine.glitchDuration)
        {
            if (glitchTargetImage != null)
            {
                // 🎨 各TextLineで設定された色変化の強度を使用
                Color glitchColor = new Color(
                    glitchOriginalColor.r + Random.Range(-textLine.colorGlitchIntensity, textLine.colorGlitchIntensity),
                    glitchOriginalColor.g + Random.Range(-textLine.colorGlitchIntensity, textLine.colorGlitchIntensity),
                    glitchOriginalColor.b + Random.Range(-textLine.colorGlitchIntensity, textLine.colorGlitchIntensity),
                    glitchOriginalColor.a
                );
                glitchTargetImage.color = glitchColor;
            }
            
            // 次のグリッチ更新まで待機
            yield return new WaitForSeconds(textLine.glitchInterval);
            
            if (!isPermanent)
            {
                elapsedTime += textLine.glitchInterval;
            }
        }
        
        // 元の状態に戻す（永続モードでは実行されない）
        ResetGlitchPanel();
        isGlitchPlaying = false;
    }

    /// <summary>
    /// 👻 グリッチパネルを元の状態に戻す
    /// </summary>
    private void ResetGlitchPanel()
    {
        if (glitchTargetImage != null)
        {
            glitchTargetImage.color = glitchOriginalColor;
        }
    }

    /// <summary>
    /// 👻 グリッチ演出を停止
    /// </summary>
    private void StopGlitchEffect()
    {
        if (glitchCoroutine != null)
        {
            StopCoroutine(glitchCoroutine);
            glitchCoroutine = null;
        }
        
        ResetGlitchPanel();
        isGlitchPlaying = false;
    }

    private IEnumerator ChangeBackgroundPanel(GameObject newPanel)
    {
        // 🖼️ 現在のTextLineのフェード時間を取得
        float fadeDuration = GetCurrentImageFadeDuration();
        
        // 前の画像をフェードアウト
        if (currentActivePanel != null)
        {
            CanvasGroup currentCanvasGroup = currentActivePanel.GetComponent<CanvasGroup>();
            if (currentCanvasGroup == null)
                currentCanvasGroup = currentActivePanel.AddComponent<CanvasGroup>();

            float elapsed = 0f;
            while (elapsed < fadeDuration / 2)
            {
                elapsed += Time.deltaTime;
                currentCanvasGroup.alpha = Mathf.Lerp(1f, 0f, elapsed / (fadeDuration / 2));
                yield return null;
            }

            currentActivePanel.SetActive(false);
        }

        // 新しい画像をフェードイン
        if (!newPanel.activeSelf)
            newPanel.SetActive(true);

        CanvasGroup newCanvasGroup = newPanel.GetComponent<CanvasGroup>();
        if (newCanvasGroup == null)
            newCanvasGroup = newPanel.AddComponent<CanvasGroup>();

        newCanvasGroup.alpha = 0f;
        float elapsedIn = 0f;
        while (elapsedIn < fadeDuration / 2)
        {
            elapsedIn += Time.deltaTime;
            newCanvasGroup.alpha = Mathf.Lerp(0f, 1f, elapsedIn / (fadeDuration / 2));
            yield return null;
        }

        newCanvasGroup.alpha = 1f;
        currentActivePanel = newPanel;
    }

    // 🖼️ 現在のPanelをフェードアウトして非表示にする
    private IEnumerator HideCurrentPanel()
    {
        if (currentActivePanel == null)
        {
            yield break;
        }

        // 🖼️ 現在のTextLineのフェード時間を取得
        float fadeDuration = GetCurrentImageFadeDuration();

        CanvasGroup canvasGroup = currentActivePanel.GetComponent<CanvasGroup>();
        if (canvasGroup == null)
            canvasGroup = currentActivePanel.AddComponent<CanvasGroup>();

        float elapsed = 0f;
        while (elapsed < fadeDuration / 2)
        {
            elapsed += Time.deltaTime;
            canvasGroup.alpha = Mathf.Lerp(1f, 0f, elapsed / (fadeDuration / 2));
            yield return null;
        }

        currentActivePanel.SetActive(false);
        currentActivePanel = null;
    }

    // 🎵 BGMを切り替える（即座に開始、バックグラウンドでフェード処理）
    private void PlayBGMForCurrentLine()
    {
        if (bgmSource == null) return;

        AudioClip clip = textLines[currentLineIndex].bgmClip;
        bool shouldLoop = textLines[currentLineIndex].loopBGM;

        if (clip != null)
        {
            if (bgmSource.clip == clip && bgmSource.isPlaying)
            {
                // 🔁 同じクリップでもループ設定が変わっている場合は更新
                bgmSource.loop = shouldLoop;
                return;
            }

            // BGM切り替えコルーチンを開始（非同期で実行）
            StartCoroutine(FadeBGM(clip, shouldLoop));
        }
        else
        {
            StartCoroutine(FadeOutBGM());
        }
    }

    // 🎧 BGMフェード付き切り替え
    private IEnumerator FadeBGM(AudioClip newClip, bool loop)
    {
        // 前のBGMをフェードアウト
        if (bgmSource.isPlaying)
        {
            float startVolume = bgmSource.volume;
            float t = 0f;
            while (t < bgmFadeDuration)
            {
                t += Time.deltaTime;
                bgmSource.volume = Mathf.Lerp(startVolume, 0f, t / bgmFadeDuration);
                yield return null;
            }
            bgmSource.Stop();
        }

        // 新しいBGMを設定して再生開始
        bgmSource.clip = newClip;
        bgmSource.loop = loop; // 🔁 ループ設定を適用
        bgmSource.volume = 0f; // 音量0から開始
        bgmSource.Play(); // すぐに再生開始

        // フェードイン
        float endVolume = (volumeSlider != null) ? volumeSlider.value : 1f;
        float t2 = 0f;
        while (t2 < bgmFadeDuration)
        {
            t2 += Time.deltaTime;
            bgmSource.volume = Mathf.Lerp(0f, endVolume, t2 / bgmFadeDuration);
            yield return null;
        }

        bgmSource.volume = endVolume;
    }

    private IEnumerator FadeOutBGM()
    {
        if (bgmSource.isPlaying)
        {
            float startVolume = bgmSource.volume;
            float t = 0f;
            while (t < bgmFadeDuration)
            {
                t += Time.deltaTime;
                bgmSource.volume = Mathf.Lerp(startVolume, 0f, t / bgmFadeDuration);
                yield return null;
            }
            bgmSource.Stop();
        }
    }

    private IEnumerator DisplayTextCoroutine()
    {
        isDisplaying = true;
        isComplete = false;
        currentText = "";
        currentIndex = 0;

        string fullText = textLines[currentLineIndex].text;
        // ⚡ 各TextLineのtextSpeedを使用（0以下の場合はデフォルト値を使用）
        float speed = textLines[currentLineIndex].textSpeed > 0 
            ? textLines[currentLineIndex].textSpeed 
            : defaultTextSpeed;

        while (currentIndex < fullText.Length)
        {
            currentText += fullText[currentIndex];
            displayText.text = displayedText + currentText;
            currentIndex++;
            yield return new WaitForSeconds(speed);
        }

        isComplete = true;
        isDisplaying = false;
        
        // ✨ マーカー表示のON/OFF切り替え
        if (useMarker)
        {
            ShowMarker();
        }
    }

    private void SkipToEnd()
    {
        StopCoroutine(DisplayTextCoroutine());
        
        currentText = textLines[currentLineIndex].text;
        displayText.text = displayedText + currentText;
        currentIndex = textLines[currentLineIndex].text.Length;
        isComplete = true;
        isDisplaying = false;
        
        // ✨ マーカー表示のON/OFF切り替え
        if (useMarker)
        {
            ShowMarker();
        }
    }

    private void ShowNextText()
    {
        HideMarker();
        
        // 👻 次のテキストに進む際にグリッチ演出を停止
        StopGlitchEffect();
        
        // 🎬 アニメーションを停止
        StopBackgroundAnimation();
        
        displayedText += currentText + "\n";
        currentLineIndex++;

        if (currentLineIndex < textLines.Length)
        {
            StartTextDisplay();
        }
        else
        {
            Debug.Log("すべてのテキストを表示しました。");

            // ✨ テキストをクリア
            displayedText = "";
            currentText = "";
            if (displayText != null)
            {
                displayText.text = "";
            }

            // 🎬 シーン遷移が有効な場合は最優先で実行
            if (useSceneTransition && !string.IsNullOrEmpty(targetSceneName))
            {
                TransitionToScene();
            }
            else if (nextTextMove != null)
            {
                SwitchToNextTextMove();
            }
            else if (alternativeObject != null)
            {
                ShowAlternativeObject();
            }
        }
    }

    /// <summary>
    /// 🎬 指定したシーンに遷移する
    /// </summary>
    private void TransitionToScene()
    {
        // 🖼️ シーン遷移前にすべての画像を非表示にする
        HideAllBackgroundPanels();
        
        // 👻 グリッチ演出を停止
        StopGlitchEffect();
        
        // 🎬 アニメーションを停止
        StopBackgroundAnimation();
        
        isActive = false;
        
        Debug.Log("シーン遷移: " + targetSceneName);
        
        if (sceneTransitionDelay > 0)
        {
            StartCoroutine(DelayedSceneTransition());
        }
        else
        {
            SceneManager.LoadScene(targetSceneName);
        }
    }

    /// <summary>
    /// 🎬 遅延付きシーン遷移
    /// </summary>
    private IEnumerator DelayedSceneTransition()
    {
        yield return new WaitForSeconds(sceneTransitionDelay);
        SceneManager.LoadScene(targetSceneName);
    }

    private void SwitchToNextTextMove()
    {
        // 🖼️ 次のTextMoveに移行する前に、現在のすべての画像を非表示にする
        HideAllBackgroundPanels();
        
        // 👻 グリッチ演出を停止
        StopGlitchEffect();
        
        // 🎬 アニメーションを停止
        StopBackgroundAnimation();
        
        isActive = false;
        this.gameObject.SetActive(false);
        nextTextMove.gameObject.SetActive(true);
        nextTextMove.ActivateAndStart();
    }

    private void ShowAlternativeObject()
    {
        // 🖼️ 代替オブジェクトに移行する前に、現在のすべての画像を非表示にする
        HideAllBackgroundPanels();
        
        // 👻 グリッチ演出を停止
        StopGlitchEffect();
        
        // 🎬 アニメーションを停止
        StopBackgroundAnimation();
        
        isActive = false;
        alternativeObject.SetActive(true);
        Debug.Log("代替オブジェクトを表示しました。");
    }

    public void ActivateAndStart()
    {
        isActive = true;
        displayedText = "";
        currentText = "";
        currentLineIndex = 0;
        currentIndex = 0;
        isDisplaying = false;
        isComplete = false;

        if (displayText == null)
        {
            displayText = GetComponent<Text>();
        }

        if (displayText != null)
        {
            displayText.supportRichText = true;
            displayText.text = "";
        }

        // ✨ マーカーを▶に変更
        markerText = "<size=" + markerSizePercent + "%>▶</size>";

        // 🖼️ アクティブ化時にすべての画像を非表示にしてリセット
        foreach (TextLine textLine in textLines)
        {
            if (textLine.backgroundPanels != null)
            {
                foreach (GameObject panel in textLine.backgroundPanels)
                {
                    if (panel != null)
                    {
                        panel.SetActive(false);
                    }
                }
            }
        }
        
        currentActivePanel = null;
        currentActivePanels = null;

        if (textLines.Length > 0)
        {
            StartTextDisplay();
        }
    }

    private void ShowMarker()
    {
        isMarkerVisible = true;
        if (blinkCoroutine != null)
        {
            StopCoroutine(blinkCoroutine);
        }
        blinkCoroutine = StartCoroutine(BlinkMarker());
    }

    private void HideMarker()
    {
        isMarkerVisible = false;
        if (blinkCoroutine != null)
        {
            StopCoroutine(blinkCoroutine);
            blinkCoroutine = null;
        }
        displayText.text = displayedText + currentText;
    }

    private IEnumerator BlinkMarker()
    {
        bool showMarker = true;

        while (isMarkerVisible)
        {
            // ✨ マーカーを改行して表示
            displayText.text = showMarker
                ? displayedText + currentText + "\n" + markerText
                : displayedText + currentText;

            showMarker = !showMarker;
            yield return new WaitForSeconds(markerBlinkSpeed);
        }
    }
}