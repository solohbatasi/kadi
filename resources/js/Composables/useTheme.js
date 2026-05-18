import { computed, ref } from 'vue';

const theme = ref(localStorage.getItem('theme') || 'system');

const prefersDark = () => window.matchMedia('(prefers-color-scheme: dark)').matches;

const applyTheme = () => {
    document.documentElement.classList.toggle('dark', theme.value === 'dark' || (theme.value === 'system' && prefersDark()));
};

const setTheme = (value) => {
    theme.value = value;
    localStorage.setItem('theme', value);
    applyTheme();
};

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', applyTheme);
applyTheme();

export function useTheme() {
    return {
        theme,
        isDark: computed(() => document.documentElement.classList.contains('dark')),
        setTheme,
        toggleTheme: () => setTheme(document.documentElement.classList.contains('dark') ? 'light' : 'dark'),
    };
}
