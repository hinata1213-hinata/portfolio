using UnityEngine;
using UnityEngine.UI;
using UnityEngine.SceneManagement;
using System;

public class ChoiceButton : MonoBehaviour
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
    
    [Header("オプション設定")]
    [Tooltip("シーン遷移時にフェードアウトを使用するか")]
    [SerializeField]
    private bool useFadeTransition = false;
    
    [Tooltip("フェード時間（秒）")]
    [SerializeField]
    private float fadeTime = 0.5f;

    void Start()
    {
        SetupButtons();
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
        Debug.Log($"ChoiceButton: シーン '{sceneName}' に遷移します。");
        
        if (useFadeTransition)
        {
            StartCoroutine(LoadSceneWithFade(sceneName));
        }
        else
        {
            LoadScene(sceneName);
        }
    }

    /// <summary>
    /// シーンを直接読み込む
    /// </summary>
    /// <param name="sceneName">シーン名</param>
    private void LoadScene(string sceneName)
    {
        SceneManager.LoadScene(sceneName);
    }

    /// <summary>
    /// フェードアウトしてからシーンを読み込む
    /// </summary>
    /// <param name="sceneName">シーン名</param>
    private System.Collections.IEnumerator LoadSceneWithFade(string sceneName)
    {
        // ここにフェードアウト処理を追加できます
        // 例: CanvasGroupのalphaを0から1に変更するなど
        
        yield return new WaitForSeconds(fadeTime);
        
        SceneManager.LoadScene(sceneName);
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
    }
}