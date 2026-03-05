function toggleTheme() {
    const root = document.documentElement;
    const next = root.dataset.theme === 'dark' ? 'light' : 'dark';

    root.dataset.theme = next;
    localStorage.setItem('theme', next);
}
