/**
 * Looking through the pages of one document without leaving the page they are listed on.
 *
 * The group travels on the mark as JSON rather than as one hidden anchor per page, so a table of
 * several documents stays a table. Each mark builds its own viewer the first time it is used and
 * keeps it afterwards, because rebuilding it on every click throws away where the reader was.
 *
 * Under the page being read there is a strip of the others, so that turning to the fourth of five
 * is one click rather than three. The strip is ours rather than the library's: none of the
 * permissively licensed viewers has one that also handles a PDF, and the list of pages is already
 * here.
 *
 * Nothing here runs until something is clicked, and if it never is, this file has cost a parse.
 */
(function () {
    "use strict";

    const built = new Map();

    /**
     * How much of the screen the panel takes.
     *
     * The library's own settings rather than a stylesheet, because that is what they are for - a
     * paper wants more of the screen than the 900 by 506 a gallery of photographs is built
     * around. A group with a strip under it gives that strip the room.
     */
    const WIDTH = "85vw";
    const TALL = "95vh";
    const WITH_STRIP = "78vh";

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

            const many = pages.length > 1;

            const viewer = GLightbox({
                elements: pages,
                width: WIDTH,
                height: many ? WITH_STRIP : TALL,
                loop: pages.length > 2,
                touchNavigation: true,
                keyboardNavigation: true,
                zoomable: true,
            });

            if (many) {
                // Built on every open rather than once: the library takes its container away when
                // it closes, and the strip is inside it.
                viewer.on("open", function () {
                    const strip = stripOf(viewer, pages);
                    if (strip !== null) {
                        follow(strip);
                    }
                });
            }

            built.set(gallery, viewer);
        }

        return built.get(gallery);
    }

    /**
     * The strip of pages, put under the one being read.
     */
    function stripOf(viewer, pages) {
        const container = document.querySelector(".glightbox-container");
        if (container === null) {
            return null;
        }

        const strip = document.createElement("div");
        strip.className = "files-strip";

        pages.forEach(function (page, index) {
            const turn = document.createElement("button");
            turn.type = "button";
            turn.className = "files-strip-page";
            turn.dataset.filesPage = String(index);

            const picture = document.createElement("img");
            picture.src = page.thumb;
            picture.alt = "";
            picture.loading = "lazy";

            // A page there is no picture of yet still turns to - it just does so without one.
            picture.addEventListener("error", function () {
                picture.remove();
                turn.classList.add("is-blank");
                turn.textContent = String(index + 1);
            });

            turn.appendChild(picture);
            turn.addEventListener("click", function () {
                viewer.goToSlide(index);
            });

            strip.appendChild(turn);
        });

        container.appendChild(strip);

        return strip;
    }

    /**
     * Keeps the mark in the strip on whichever page is being read.
     *
     * The page is read off the library's own mark on it rather than from an event. Which of the
     * two arrows, which key, a swipe or one of ours was used then stops mattering, and so does
     * what any given version of the library announces - the class moves, and so does this. It is
     * also the only thing that is true by definition: the marked page is the page on screen.
     */
    function follow(strip) {
        const slider = document.querySelector(".gslider");
        if (slider === null) {
            return;
        }

        const showing = function () {
            const slides = slider.querySelectorAll(".gslide");
            const current = slider.querySelector(".gslide.current");
            const index = Array.prototype.indexOf.call(slides, current);

            strip.querySelectorAll(".files-strip-page").forEach(function (page) {
                page.classList.remove("is-here");
            });

            const here = strip.querySelector('[data-files-page="' + index + '"]');
            if (here) {
                here.classList.add("is-here");
                here.scrollIntoView({ block: "nearest", inline: "center" });
            }
        };

        const watching = new MutationObserver(showing);
        watching.observe(slider, { attributes: true, attributeFilter: ["class"], subtree: true });

        // The strip goes with the container the library takes away, so there is nothing left to
        // watch for once that has happened.
        const stopping = new MutationObserver(function () {
            if (!strip.isConnected) {
                watching.disconnect();
                stopping.disconnect();
            }
        });
        stopping.observe(document.body, { childList: true });

        showing();
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
