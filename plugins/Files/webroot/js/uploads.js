/**
 * Saying at once that a batch is more than the server will take.
 *
 * PHP takes `max_file_uploads` files out of a request and drops the rest before anything of ours
 * is running, so a batch chosen over the limit is already half gone by the time it could be
 * reported. The server says so afterwards, which is the backstop; this says so while there is
 * still something to do about it.
 *
 * Counted across every file field of the form rather than the one just used, because the limit is
 * on the request and a page offering one field per kind of document reaches it between them.
 *
 * The size is watched as well as the count, and it is the worse of the two to reach: past the
 * count PHP drops what is over and the rest arrives, while past `post_max_size` it throws the
 * whole request away - no files, no fields, and the form comes back complaining about something
 * else entirely.
 *
 * The form says what its own limits are, so nothing here knows anything about PHP. Without the
 * script the form still works and the server still reports the count.
 */
(function () {
    "use strict";

    /**
     * How many files are chosen in this form altogether, and how much they come to.
     */
    function chosen(form) {
        let howMany = 0;
        let bytes = 0;

        form.querySelectorAll('input[type="file"]').forEach(function (input) {
            const files = input.files || [];

            howMany += files.length;

            for (let at = 0; at < files.length; at += 1) {
                bytes += files[at].size || 0;
            }
        });

        return {howMany: howMany, bytes: bytes};
    }

    /**
     * The way the server writes a size, so that the two read alike.
     *
     * The decimal mark is the reader's rather than one written in here: the server writes it the
     * way the application writes every other number, and a full stop where the page says comma
     * reads as a different number.
     */
    function readableSize(bytes) {
        const units = ["KB", "MB", "GB"];
        let size = bytes / 1024;
        let at = 0;

        while (size >= 1024 && at < units.length - 1) {
            size /= 1024;
            at += 1;
        }

        const written = size.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        return written + " " + units[at];
    }

    /**
     * The line that says so, made when it is first needed and kept afterwards.
     */
    function noticeIn(form) {
        let notice = form.querySelector(".files-too-many");


        if (notice === null) {
            notice = document.createElement("p");
            notice.className = "files-too-many";
            notice.hidden = true;
            notice.setAttribute("role", "alert");

            const submit = form.querySelector('button[type="submit"], input[type="submit"], button');
            if (submit === null) {
                form.appendChild(notice);
            } else {
                submit.parentNode.insertBefore(notice, submit);
            }
        }

        return notice;
    }

    /**
     * What is wrong with what has been chosen, or an empty string where nothing is.
     *
     * The size is looked at first. Reaching both at once is one mistake and not two, and it is
     * the size that makes the more baffling failure of the two.
     */
    function wrongWith(form, picked) {
        const atMostBytes = Number(form.dataset.filesAtMostBytes) || 0;
        const atMost = Number(form.dataset.filesAtMost) || 0;

        if (atMostBytes > 0 && picked.bytes > atMostBytes) {
            return form.dataset.filesTooLarge.replace("%s", readableSize(picked.bytes));
        }

        if (atMost > 0 && picked.howMany > atMost) {
            return form.dataset.filesTooMany.replace("%d", String(picked.howMany));
        }

        return "";
    }

    function look(form) {
        const wrong = wrongWith(form, chosen(form));
        const notice = noticeIn(form);

        notice.hidden = wrong === "";
        notice.textContent = wrong;

        form.querySelectorAll('button[type="submit"], input[type="submit"], button').forEach(
            function (submit) {
                submit.disabled = wrong !== "";
            },
        );
    }

    document.addEventListener("change", function (event) {
        const input = event.target;
        if (input.type !== "file") {
            return;
        }

        const form = input.closest("form[data-files-at-most], form[data-files-at-most-bytes]");
        if (form !== null) {
            look(form);
        }
    });
})();
