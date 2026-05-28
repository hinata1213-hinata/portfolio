using UnityEngine;
using UnityEngine.EventSystems;

public class AchievementResetButton : MonoBehaviour, IPointerClickHandler
{
    [Header("タイトルの実績表示管理")]
    public TitleAchievementDisplay achievementDisplay;

    public void OnPointerClick(PointerEventData eventData)
    {
        if (eventData.button != PointerEventData.InputButton.Left)
            return;

        // PlayerPrefs を削除
        foreach (var ach in achievementDisplay.achievements)
        {
            PlayerPrefs.DeleteKey(ach.endID);
        }
        PlayerPrefs.Save();

        // ★ 即座にUIを更新
        achievementDisplay.RefreshDisplay();

        Debug.Log("実績をリセットし、UIを更新しました");
    }
}
