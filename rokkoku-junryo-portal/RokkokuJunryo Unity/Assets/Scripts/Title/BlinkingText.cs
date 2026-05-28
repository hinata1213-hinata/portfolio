using UnityEngine;
using UnityEngine.UI;

public class BlinkingText : MonoBehaviour
{
    [Header("点滅設定")]
    [SerializeField] private float blinkSpeed = 1f; // 点滅速度
    
    private Text uiText;
    private Color originalColor;

    void Start()
    {
        uiText = GetComponent<Text>();
        if (uiText != null)
        {
            originalColor = uiText.color;
        }
        else
        {
            Debug.LogError("UI Text component not found!");
        }
    }

    void Update()
    {
        if (uiText == null) return;

        // Sin波を使って滑らかに点滅
        float alpha = Mathf.Abs(Mathf.Sin(Time.time * blinkSpeed));
        Color newColor = originalColor;
        newColor.a = alpha;
        uiText.color = newColor;
    }
}