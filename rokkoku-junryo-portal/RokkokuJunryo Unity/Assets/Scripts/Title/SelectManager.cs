using UnityEngine;
using System.Collections;

public class SelectManager : MonoBehaviour
{
    [SerializeField] private GameObject panel; // インスペクターでパネルを設定
    [SerializeField] private float fadeDuration = 0.5f; // フェードにかかる時間（秒）
    
    private CanvasGroup panelCanvasGroup;
    private bool isPanelOpen = false;
    private bool isAnimating = false; // アニメーション中かどうか

    void Start()
    {
        // パネルにCanvasGroupがあるか確認、なければ追加
        if (panel != null)
        {
            panelCanvasGroup = panel.GetComponent<CanvasGroup>();
            if (panelCanvasGroup == null)
            {
                panelCanvasGroup = panel.AddComponent<CanvasGroup>();
            }
            
            // 初期状態：パネルを非表示に設定
            panel.SetActive(true); // パネルを有効化（CanvasGroupで透明度を制御）
            panelCanvasGroup.alpha = 0f;
            panelCanvasGroup.interactable = false;
            panelCanvasGroup.blocksRaycasts = false;
        }
    }

    void Update()
    {
        // Enterキーまたは画面クリック（左クリック）が押されたとき
        if ((Input.GetKeyDown(KeyCode.Return) || Input.GetKeyDown(KeyCode.KeypadEnter) || Input.GetMouseButtonDown(0)) 
            && !isAnimating && !isPanelOpen)
        {
            if (panel == null)
            {
                return;
            }
            
            StartCoroutine(FadeIn());
        }
    }

    private IEnumerator FadeIn()
    {
        isAnimating = true;
        isPanelOpen = true;
        
        panel.SetActive(true); // パネルを有効化
        
        panelCanvasGroup.interactable = true;
        panelCanvasGroup.blocksRaycasts = true;
        
        float elapsedTime = 0f;
        
        while (elapsedTime < fadeDuration)
        {
            elapsedTime += Time.deltaTime;
            panelCanvasGroup.alpha = Mathf.Lerp(0f, 1f, elapsedTime / fadeDuration);
            yield return null;
        }
        
        panelCanvasGroup.alpha = 1f;
        isAnimating = false;
    }
}