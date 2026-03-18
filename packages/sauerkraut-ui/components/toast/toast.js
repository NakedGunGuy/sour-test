function showToast(message, variant, duration) {
    variant = variant || 'info';
    duration = duration || 5000;

    var container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    var id = 'toast-' + Date.now();
    var toast = document.createElement('div');
    toast.id = id;
    toast.className = 'toast toast-' + variant;
    toast.innerHTML = '<div class="toast-content">' + message + '</div>' +
        '<button class="toast-dismiss" onclick="dismissToast(\'' + id + '\')" type="button">&times;</button>';

    container.appendChild(toast);

    if (duration > 0) {
        setTimeout(function() { dismissToast(id); }, duration);
    }
}

function dismissToast(id) {
    var toast = document.getElementById(id);
    if (!toast) return;

    toast.style.animation = 'toast-out 0.2s ease-in forwards';
    setTimeout(function() { toast.remove(); }, 200);
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toast[data-duration]').forEach(function(toast) {
        var duration = parseInt(toast.dataset.duration);
        if (duration > 0) {
            setTimeout(function() { dismissToast(toast.id); }, duration);
        }
    });
});
