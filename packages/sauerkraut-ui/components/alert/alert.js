function dismissAlert(button) {
    const alert = button.closest('.alert');
    alert.style.opacity = '0';
    alert.style.transform = 'translateY(-0.5rem)';
    setTimeout(() => alert.remove(), 200);
}
