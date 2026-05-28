using UnityEngine;
using UnityEngine.UI;

public class WebButton : MonoBehaviour
{
    [SerializeField]
    private string url = "https://example.com";

    void Start()
    {
        Button button = GetComponent<Button>();
        if (button != null)
        {
            button.onClick.AddListener(OpenURL);
        }
    }

    private void OpenURL()
    {
        Application.OpenURL(url);
    }

    private void OnDestroy()
    {
        Button button = GetComponent<Button>();
        if (button != null)
        {
            button.onClick.RemoveListener(OpenURL);
        }
    }
}