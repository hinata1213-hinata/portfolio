using UnityEngine;

public class EndAchievement : MonoBehaviour
{
    [Header("このENDのID（例: END1, END2, END3...）")]
    public string endID = "END1";

    void Start()
    {
        // このENDをクリア済みとして保存（1 = クリア済み）
        PlayerPrefs.SetInt(endID, 1);
        PlayerPrefs.Save();
        
        Debug.Log(endID + " をクリアしました！");
    }
}