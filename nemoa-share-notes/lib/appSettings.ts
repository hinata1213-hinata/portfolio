import AsyncStorage from '@react-native-async-storage/async-storage';

export interface AppSettings {
  darkMode: boolean;
  blockSensitiveOpen: boolean;
}

const KEY = '@qrmemo_settings_v1';

export const DEFAULT_SETTINGS: AppSettings = {
  darkMode: false,
  blockSensitiveOpen: false,
};

export async function loadSettings(): Promise<AppSettings> {
  try {
    const json = await AsyncStorage.getItem(KEY);
    if (!json) return DEFAULT_SETTINGS;
    return { ...DEFAULT_SETTINGS, ...JSON.parse(json) };
  } catch {
    return DEFAULT_SETTINGS;
  }
}

export async function saveSettings(settings: AppSettings): Promise<void> {
  await AsyncStorage.setItem(KEY, JSON.stringify(settings));
}
