using UnityEngine;
using UnityEngine.UI;

[DisallowMultipleComponent]
[RequireComponent(typeof(Button))]
public class PanelMove : MonoBehaviour
{
    [Header("表示するパネル（任意）")]
    [SerializeField] private GameObject showPanel;

    [Header("非表示にするパネル（任意）")]
    [SerializeField] private GameObject hidePanel;

    private Button button;

    private void Awake()
    {
        button = GetComponent<Button>();
        if (button != null)
        {
            button.onClick.AddListener(OnClick);
        }
    }

    private void Update()
    {
        // 表示パネルがある & 表示中のときだけ Esc で閉じる
        if (showPanel != null && showPanel.activeSelf && Input.GetKeyDown(KeyCode.Escape))
        {
            showPanel.SetActive(false);
        }
    }

    private void OnClick()
    {
        // 表示
        if (showPanel != null)
        {
            showPanel.SetActive(true);
        }

        // 非表示
        if (hidePanel != null)
        {
            hidePanel.SetActive(false);
        }
    }

    private void OnDestroy()
    {
        if (button != null)
        {
            button.onClick.RemoveListener(OnClick);
        }
    }
}
