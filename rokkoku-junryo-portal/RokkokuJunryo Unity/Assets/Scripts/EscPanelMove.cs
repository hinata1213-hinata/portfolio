using UnityEngine;

public class EscPanelMove : MonoBehaviour
{
    [SerializeField] private GameObject panel; // インスペクターからPanelをアタッチ

    void Start()
    {
        // 最初は非表示にしておく（必要に応じてコメントアウト）
        if (panel != null)
        {
            panel.SetActive(false);
        }
    }

    void Update()
    {
        // エスケープキーが押されたかチェック
        if (Input.GetKeyDown(KeyCode.Escape))
        {
            if (panel != null)
            {
                // 現在の表示状態を反転させる
                panel.SetActive(!panel.activeSelf);
            }
        }
    }
}