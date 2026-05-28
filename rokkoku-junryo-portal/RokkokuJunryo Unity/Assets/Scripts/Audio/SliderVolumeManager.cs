using UnityEngine;
using UnityEngine.UI;
using UnityEngine.Audio;

public class SliderVolumeManager : MonoBehaviour
{
    [SerializeField] private AudioMixer audioMixer;
    [SerializeField] private Slider volumeSlider;
    private const string VolumeKey = "BGM-Volume"; // PlayerPrefs用のキー

    private void Start()
    {
        // 前回保存した音量を読み込む（デフォルト値は1.0）
        float savedVolume = PlayerPrefs.GetFloat(VolumeKey, 1.0f);
        volumeSlider.value = savedVolume;
        SetVolume(savedVolume);

        if (volumeSlider != null)
        {
            volumeSlider.onValueChanged.AddListener((value) =>
            {
                value = Mathf.Clamp01(value);
                SetVolume(value);
                PlayerPrefs.SetFloat(VolumeKey, value); // 音量を保存
                PlayerPrefs.Save(); // データを確実に保存
            });
        }
    }

    private void SetVolume(float value)
    {
        float decibel = (value > 0) ? 20f * Mathf.Log10(value) : -80f;
        audioMixer.SetFloat("BGM-Volume", decibel);
    }
}