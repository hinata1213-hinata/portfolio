using UnityEngine;
using UnityEngine.UI;

public class AllEndsClearButton : MonoBehaviour
{
    [Header("表示させたいButton")]
    public GameObject targetButton;

    [Header("必要なENDのID一覧")]
    public string[] requiredEndIDs = { "END1", "END2", "END3", "END4", "END5", "END6" };

    void Start()
    {
        // 全ENDクリアしているかチェック
        bool allCleared = CheckAllEndsCleared();

        // 全クリアなら表示、そうでなければ非表示
        targetButton.SetActive(allCleared);

        if (allCleared)
        {
            Debug.Log("全ENDクリア！ボタンを表示しました");
        }
    }

    /// <summary>
    /// 全てのENDがクリアされているかチェック
    /// </summary>
    private bool CheckAllEndsCleared()
    {
        foreach (string endID in requiredEndIDs)
        {
            // 1つでも未クリア（0）があればfalse
            if (PlayerPrefs.GetInt(endID, 0) != 1)
            {
                Debug.Log(endID + " が未クリアです");
                return false;
            }
        }
        return true;
    }
}