function toggleDropdown(id) {
    const dropdown = document.getElementById(id);
    const wasActive = dropdown.classList.contains('active');

    document.querySelectorAll('.dropdown.active').forEach(function(el) {
        el.classList.remove('active');
    });

    if (!wasActive) {
        dropdown.classList.add('active');
    }
}

document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown.active').forEach(function(el) {
            el.classList.remove('active');
        });
    }
});
