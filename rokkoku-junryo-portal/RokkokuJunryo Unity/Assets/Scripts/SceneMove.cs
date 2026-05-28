using UnityEngine;
using UnityEngine.UI;
using UnityEngine.SceneManagement;
using System.Collections;

public class SceneMove : MonoBehaviour
{
    [Header("表示設定")]
    [SerializeField] private Image[] fadeImages; // 表示する画像の配列
    [SerializeField] private float displayDuration = 2.0f; // 表示時間
    
    [Header("シーン設定")]
    [SerializeField] private string nextSceneName; // 遷移先のシーン名
    [SerializeField] private bool startOnAwake = true; // 起動時に自動開始するか

    [Header("サウンド設定")]
    [SerializeField] private AudioSource bgmSource; // BGM再生用AudioSource
    [SerializeField] private Slider volumeSlider;   // 音量調整スライダー
    [SerializeField] private AudioClip bgmClip; // 🎵 このシーンで再生するBGM
    [SerializeField] private bool loopBGM = true; // 🔁 BGMをループするかどうか

    void Start()
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

        if (startOnAwake)
        {
            StartDisplaySequence();
        }
    }

    private void OnVolumeChanged(float value)
    {
        if (bgmSource != null)
        {
            bgmSource.volume = value;
        }
    }

    public void StartDisplaySequence()
    {
        StartCoroutine(DisplaySequence());
    }

    private IEnumerator DisplaySequence()
    {
        if (fadeImages == null || fadeImages.Length == 0)
        {
            Debug.LogError("画像が設定されていません！");
            yield break;
        }

        // 画像を表示
        foreach (Image img in fadeImages)
        {
            if (img != null)
            {
                img.gameObject.SetActive(true);
            }
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
        if (!string.IsNullOrEmpty(nextSceneName))
        {
            SceneManager.LoadScene(nextSceneName);
        }
        else
        {
            Debug.LogWarning("次のシーン名が設定されていません！");
        }
    }

    void OnDestroy()
    {
        if (volumeSlider != null)
        {
            volumeSlider.onValueChanged.RemoveListener(OnVolumeChanged);
        }
    }
}