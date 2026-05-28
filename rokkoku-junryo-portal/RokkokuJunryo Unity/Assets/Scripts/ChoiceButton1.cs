using UnityEngine;
using UnityEngine.UI;
using UnityEngine.SceneManagement;
using System;
using System.Collections;

public class ChoiceButton1 : MonoBehaviour
{
    [Serializable]
    public class ButtonSceneMapping
    {
        [Tooltip("対象のボタン")]
        public Button button;
        
        [Tooltip("遷移先のシーン名")]
        public string sceneName;
        
        [Tooltip("ボタンの説明（任意）")]
        public string description;
    }
    
    [Header("ボタンとシーンの設定")]
    [Tooltip("ボタンと遷移先シーンのペアを設定してください")]
    [SerializeField]
    private ButtonSceneMapping[] buttonMappings;
    
    [Header("表示設定")]
    [SerializeField] private Image[] fadeImages; // 表示する画像の配列
    [SerializeField] private float displayDuration = 2.0f; // 表示時間

    [Header("サウンド設定")]
    [SerializeField] private AudioSource bgmSource; // BGM再生用AudioSource
    [SerializeField] private Slider volumeSlider;   // 音量調整スライダー
    [SerializeField] private AudioClip bgmClip; // このシーンで再生するBGM
    [SerializeField] private bool loopBGM = true; // BGMをループするかどうか

    private bool isTransitioning = false; // 遷移中フラグ

    void Start()
    {
        SetupButtons();
        SetupAudio();
        HideFadeImages(); // 開始時は画像を非表示に
    }

    /// <summary>
    /// AudioSourceの初期化
    /// </summary>
    private void SetupAudio()
    {
        // AudioSourceの初期化
        if (bgmSource == null)
        {
            bgmSource = GetComponent<AudioSource>();
            if (bgmSource == null)
            {
                bgmSource = gameObject.AddComponent<AudioSource>();
            }
        }

        // スライダーのリスナー設定
        if (volumeSlider != null)
        {
            volumeSlider.onValueChanged.AddListener(OnVolumeChanged);
            if (bgmSource != null)
            {
                bgmSource.volume = volumeSlider.value;
            }
        }
    }

    /// <summary>
    /// 画像を非表示にする
    /// </summary>
    private void HideFadeImages()
    {
        if (fadeImages != null)
        {
            foreach (Image img in fadeImages)
            {
                if (img != null)
                {
                    img.gameObject.SetActive(false);
                }
            }
        }
    }

    private void OnVolumeChanged(float value)
    {
        if (bgmSource != null)
        {
            bgmSource.volume = value;
        }
    }

    /// <summary>
    /// すべてのボタンにクリックイベントを設定
    /// </summary>
    private void SetupButtons()
    {
        if (buttonMappings == null || buttonMappings.Length == 0)
        {
            Debug.LogWarning("ChoiceButton: ボタンマッピングが設定されていません。");
            return;
        }

        foreach (var mapping in buttonMappings)
        {
            if (mapping.button == null)
            {
                Debug.LogWarning("ChoiceButton: ボタンが設定されていない項目があります。");
                continue;
            }

            if (string.IsNullOrEmpty(mapping.sceneName))
            {
                Debug.LogWarning($"ChoiceButton: ボタン '{mapping.button.name}' のシーン名が設定されていません。");
                continue;
            }

            // ローカル変数にキャプチャしてクロージャの問題を回避
            string targetScene = mapping.sceneName;
            
            mapping.button.onClick.AddListener(() => OnButtonClicked(targetScene));
        }
    }

    /// <summary>
    /// ボタンがクリックされたときの処理
    /// </summary>
    /// <param name="sceneName">遷移先のシーン名</param>
    private void OnButtonClicked(string sceneName)
    {
        if (isTransitioning) return; // 遷移中は無視
        
        Debug.Log($"ChoiceButton: シーン '{sceneName}' に遷移します。");
        StartCoroutine(DisplaySequence(sceneName));
    }

    /// <summary>
    /// 画像表示シーケンス（SceneMoveと同じ機能）
    /// </summary>
    /// <param name="sceneName">遷移先のシーン名</param>
    private IEnumerator DisplaySequence(string sceneName)
    {
        isTransitioning = true;

        // 画像を表示
        if (fadeImages != null && fadeImages.Length > 0)
        {
            foreach (Image img in fadeImages)
            {
                if (img != null)
                {
                    img.gameObject.SetActive(true);
                }
            }
        }
        else
        {
            Debug.LogWarning("ChoiceButton: 画像が設定されていません！");
        }

        // BGM再生
        if (bgmClip != null && bgmSource != null)
        {
            bgmSource.clip = bgmClip;
            bgmSource.loop = loopBGM;
            bgmSource.Play();
        }

        // 指定時間待機
        yield return new WaitForSeconds(displayDuration);

        // シーン遷移
        if (!string.IsNullOrEmpty(sceneName))
        {
            SceneManager.LoadScene(sceneName);
        }
        else
        {
            Debug.LogWarning("ChoiceButton: 次のシーン名が設定されていません！");
            isTransitioning = false;
        }
    }

    /// <summary>
    /// スクリプトからボタンマッピングを追加
    /// </summary>
    /// <param name="button">ボタン</param>
    /// <param name="sceneName">シーン名</param>
    /// <param name="description">説明（任意）</param>
    public void AddButtonMapping(Button button, string sceneName, string description = "")
    {
        if (button == null || string.IsNullOrEmpty(sceneName))
        {
            Debug.LogError("ChoiceButton: ボタンまたはシーン名が無効です。");
            return;
        }

        string targetScene = sceneName;
        button.onClick.AddListener(() => OnButtonClicked(targetScene));
    }

    /// <summary>
    /// 特定のボタンのシーン名を取得
    /// </summary>
    /// <param name="button">対象のボタン</param>
    /// <returns>シーン名（見つからない場合はnull）</returns>
    public string GetSceneNameForButton(Button button)
    {
        foreach (var mapping in buttonMappings)
        {
            if (mapping.button == button)
            {
                return mapping.sceneName;
            }
        }
        return null;
    }

    void OnDestroy()
    {
        // リスナーをクリーンアップ
        if (buttonMappings != null)
        {
            foreach (var mapping in buttonMappings)
            {
                if (mapping.button != null)
                {
                    mapping.button.onClick.RemoveAllListeners();
                }
            }
        }

        if (volumeSlider != null)
        {
            volumeSlider.onValueChanged.RemoveListener(OnVolumeChanged);
        }
    }
}