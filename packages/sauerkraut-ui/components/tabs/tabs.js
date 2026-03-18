function switchTab(button, index) {
    const tabs = button.closest('.tabs');

    tabs.querySelectorAll('.tab-button').forEach(function(btn) {
        btn.classList.remove('active');
    });

    tabs.querySelectorAll('.tab-content').forEach(function(content) {
        content.classList.remove('active');
    });

    button.classList.add('active');
    var panels = tabs.querySelectorAll('.tab-panel');
    if (panels[index]) {
        panels[index].querySelector('.tab-content').classList.add('active');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.tabs').forEach(function(tabs) {
        var activeIndex = parseInt(tabs.dataset.active || '0');
        var panels = tabs.querySelectorAll('.tab-panel');

        // Build tab button bar
        var buttonBar = document.createElement('div');
        buttonBar.className = 'tab-buttons';

        panels.forEach(function(panel, i) {
            var button = panel.querySelector('.tab-button');
            if (button) {
                button.style.display = '';
                buttonBar.appendChild(button);

                if (i === activeIndex) {
                    button.classList.add('active');
                    panel.querySelector('.tab-content').classList.add('active');
                }
            }
        });

        tabs.insertBefore(buttonBar, tabs.firstChild);
    });
});
