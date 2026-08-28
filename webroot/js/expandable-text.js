document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.expandable-text').forEach(wrapper => {
        const viewport = wrapper.querySelector('.expandable-text-viewport');
        const content = wrapper.querySelector('.expandable-text-content');
        const toggle = wrapper.querySelector('.toggle');
        const more = wrapper.dataset.more;
        const less = wrapper.dataset.less;
        const lines = parseInt(wrapper.dataset.lines, 10);
        const mode = wrapper.classList.contains('mode-end') ? 'end' : 'start';

        // set max lines dynamicly in CSS
        wrapper.style.setProperty('--lines', lines);

        function applyEndMode(lineHeight) {
            const visibleHeight = lineHeight * lines;
            const fullHeight = content.scrollHeight;

            if (fullHeight > visibleHeight) {
                const offset = fullHeight - visibleHeight;
                content.style.transform = `translateY(-${offset}px)`;
            }
        }

        function update() {
            // recompute line height
            const lineHeight = parseFloat(getComputedStyle(content).lineHeight);
            wrapper.style.setProperty('--line-height', lineHeight + 'px');

            // over a text that fits whole, a fade reads as a rendering fault rather than as
            // "there is more", so it is drawn only where something was really cut off
            wrapper.classList.toggle('overflowing', content.scrollHeight > lineHeight * lines);

            // check if toggle is needed
            if (content.scrollHeight <= lineHeight * lines) {
                toggle.style.display = 'none';
                content.style.transform = 'none';
                return;
            } else {
                toggle.style.display = '';
            }

            // apply end mode only when collapsed
            if (mode === 'end' && !wrapper.classList.contains('expanded')) {
                applyEndMode(lineHeight);
            }
        }

        // initial calculation
        update();

        toggle.addEventListener('click', () => {
            wrapper.classList.toggle('expanded');

            if (wrapper.classList.contains('expanded')) {
                content.style.transform = 'none';
                toggle.textContent = less;
            } else {
                toggle.textContent = more;
                update(); // recalc after collapse
            }
        });

        // debounce resize
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(update, 150);
        });
    });
});
