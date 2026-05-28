using UnityEngine;
using UnityEngine.UI;

public class ButtonUp : MonoBehaviour
{
    [SerializeField]
    private Button targetButton; // Inspectorでアタッチするボタン

    void Start()
    {
        // アタッチされたボタンが存在し、表示状態であれば
        if (targetButton != null && targetButton.gameObject.activeInHierarchy)
        {
            RectTransform rectTransform = GetComponent<RectTransform>();
            if (rectTransform != null)
            {
                Vector2 pos = rectTransform.anchoredPosition;
                pos.y += 100f;
                rectTransform.anchoredPosition = pos;
            }
        }
    }

    void Update()
    {
        
    }
}