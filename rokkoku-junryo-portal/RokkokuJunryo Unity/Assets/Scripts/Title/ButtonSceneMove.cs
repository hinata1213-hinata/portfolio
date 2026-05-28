using UnityEngine;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

public class ButtonSceneMove : MonoBehaviour
{
    [SerializeField] private string sceneName; // Inspectorでシーン名を指定

    void Start()
    {
        // ボタンコンポーネントを取得してクリックイベントを設定
        Button button = GetComponent<Button>();
        if (button != null)
        {
            button.onClick.AddListener(MoveToScene);
        }
        else
        {
            Debug.LogError("Buttonコンポーネントが見つかりません！");
        }
    }

    // シーンを移動するメソッド
    void MoveToScene()
    {
        if (!string.IsNullOrEmpty(sceneName))
        {
            SceneManager.LoadScene(sceneName);
        }
        else
        {
            Debug.LogError("シーン名が設定されていません！");
        }
    }
}