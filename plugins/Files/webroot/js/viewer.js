/**
 * Looking through the pages of one document without leaving the page they are listed on.
 *
 * The group travels on the mark as JSON rather than as one hidden anchor per page, so a table of
 * several documents stays a table. Each mark builds its own viewer the first time it is used and
 * keeps it afterwards, because rebuilding it on every click throws away where the reader was.
 *
 * Nothing here runs until something is clicked, and if it never is, this file has cost a parse.
 */
(function () {
    "use strict";

    const built = new Map();

    /**
     * The viewer for one group, made when it is first asked for.
     */
    function viewerFor(mark) {
        const gallery = mark.dataset.filesGallery;

        if (!built.has(gallery)) {
            let pages;
            try {
                pages = JSON.parse(mark.dataset.filesPages);
            } catch (error) {
                // A mark we cannot read is a mark we leave alone: the link under it still works.
                console.error("Files viewer: could not read the pages of " + gallery, error);
                return null;
            }

            built.set(gallery, GLightbox({
                elements: pages,
                loop: pages.length > 2,
                touchNavigation: true,
                keyboardNavigation: true,
                zoomable: true,
            }));
        }

        return built.get(gallery);
    }

    document.addEventListener("click", function (event) {
        const mark = event.target.closest("[data-files-pages]");
        if (!mark || typeof GLightbox !== "function") {
            return;
        }

        // Anything but a plain left click is the reader asking for a tab of their own, and the
        // href under the mark is there to give them one.
        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const viewer = viewerFor(mark);
        if (viewer === null) {
            return;
        }

        event.preventDefault();
        viewer.openAt(Number(mark.dataset.filesStart) || 0);
    });
})();
