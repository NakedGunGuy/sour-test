function toggleAccordion(trigger) {
    var item = trigger.closest('.accordion-item');
    var accordion = trigger.closest('.accordion');
    var allowMultiple = accordion.dataset.multiple === 'true';
    var content = item.querySelector('.accordion-content');
    var isActive = trigger.classList.contains('active');

    if (!allowMultiple) {
        accordion.querySelectorAll('.accordion-trigger.active').forEach(function(other) {
            if (other !== trigger) {
                other.classList.remove('active');
                other.closest('.accordion-item').querySelector('.accordion-content').classList.remove('active');
            }
        });
    }

    if (isActive) {
        trigger.classList.remove('active');
        content.classList.remove('active');
    } else {
        trigger.classList.add('active');
        content.classList.add('active');
    }
}
