document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.expandable-text').forEach(wrapper => {
        const viewport = wrapper.querySelector('.expandable-text-viewport');
        const content = wrapper.querySelector('.expandable-text-content');
        const toggle = wrapper.querySelector('.toggle');
        const more = wrapper.dataset.more;
        const less = wrapper.dataset.less;
        const lines = wrapper.dataset.lines;
        const mode = wrapper.classList.contains('mode-end') ? 'end' : 'start';

        // set max lines dynamicly in CSS
        wrapper.style.setProperty('--lines', lines);

        // get actual line height (in px)
        const lineHeight = parseFloat(getComputedStyle(content).lineHeight);
        wrapper.style.setProperty('--line-height', lineHeight + 'px');

        // move content up (overflow hidden)
        function applyEndMode() {
            const visibleHeight = lineHeight * lines;
            const fullHeight = content.scrollHeight;

            if (fullHeight > visibleHeight) {
                const offset = fullHeight - visibleHeight;
                content.style.transform = `translateY(-${offset}px)`;
            }
        }

        if (mode === 'end') {
            applyEndMode();
        }

        toggle.addEventListener('click', () => {
            wrapper.classList.toggle('expanded');

            if (wrapper.classList.contains('expanded')) {
                content.style.transform = 'none';
                toggle.textContent = less;
            } else {
                if (mode === 'end') applyEndMode();
                toggle.textContent = more;
            }
        });
    });
});
