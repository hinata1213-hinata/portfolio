using UnityEngine;

public class TitleAchievementDisplay : MonoBehaviour
{
    [System.Serializable]
    public class EndAchievementUI
    {
        public string endID;
        public GameObject textObject;
    }

    [Header("各ENDと対応するTextを設定")]
    public EndAchievementUI[] achievements;

    [Header("全クリア時に表示するテキスト")]
    public GameObject allClearText;

    void Start()
    {
        RefreshDisplay();
    }

    // ★ 外部から呼べるようにする
    public void RefreshDisplay()
    {
        bool allCleared = true;

        foreach (var achievement in achievements)
        {
            bool isCleared = PlayerPrefs.GetInt(achievement.endID, 0) == 1;
            achievement.textObject.SetActive(isCleared);

            if (!isCleared)
                allCleared = false;
        }

        allClearText.SetActive(allCleared);
    }
}
