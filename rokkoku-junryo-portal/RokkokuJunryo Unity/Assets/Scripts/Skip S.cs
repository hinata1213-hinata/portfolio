using UnityEngine;

public class SkipS : MonoBehaviour
{
    [Header("Sキーで表示するPanel")]
    [SerializeField] private GameObject panelToShow;

    [Header("Sキーで非表示にするオブジェクト（複数）")]
    [SerializeField] private GameObject[] objectsToHide;

    [Header("停止するAudioSource（BGM / SE）")]
    [SerializeField] private AudioSource[] audioSourcesToStop;

    // 1回だけ実行するためのフラグ
    private bool hasSkipped = false;

    void Update()
    {
        if (hasSkipped) return;

        if (Input.GetKeyDown(KeyCode.S))
        {
            ExecuteSkip();
            hasSkipped = true; // ← 以降は無効
        }
    }

    private void ExecuteSkip()
    {
        // Panelを表示
        if (panelToShow != null)
            panelToShow.SetActive(true);

        // オブジェクトを非表示
        if (objectsToHide != null)
        {
            foreach (var obj in objectsToHide)
            {
                if (obj != null)
                    obj.SetActive(false);
            }
        }

        // Audio停止
        if (audioSourcesToStop != null)
        {
            foreach (var audio in audioSourcesToStop)
            {
                if (audio != null && audio.isPlaying)
                {
                    audio.Stop();
                }
            }
        }
    }
}
