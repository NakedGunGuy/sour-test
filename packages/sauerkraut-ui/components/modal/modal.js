function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function closeModalOnOverlay(event) {
    if (event.target === event.currentTarget) {
        event.target.classList.remove('active');
        document.body.style.overflow = '';
    }
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const active = document.querySelector('.modal-overlay.active');
        if (active) {
            active.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
});
