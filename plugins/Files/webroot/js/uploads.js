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
 * The form says what its own limit is, so nothing here knows anything about PHP. Without the
 * script the form still works and the server still reports it.
 */
(function () {
    "use strict";

    /**
     * How many files are chosen in this form altogether.
     */
    function chosen(form) {
        let howMany = 0;

        form.querySelectorAll('input[type="file"]').forEach(function (input) {
            howMany += input.files ? input.files.length : 0;
        });

        return howMany;
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

    function look(form) {
        const atMost = Number(form.dataset.filesAtMost) || 0;
        if (atMost < 1) {
            return;
        }

        const howMany = chosen(form);
        const tooMany = howMany > atMost;
        const notice = noticeIn(form);

        notice.hidden = !tooMany;
        notice.textContent = tooMany ? form.dataset.filesTooMany.replace("%d", String(howMany)) : "";

        form.querySelectorAll('button[type="submit"], input[type="submit"], button').forEach(
            function (submit) {
                submit.disabled = tooMany;
            },
        );
    }

    document.addEventListener("change", function (event) {
        const input = event.target;
        if (input.type !== "file") {
            return;
        }

        const form = input.closest("form[data-files-at-most]");
        if (form !== null) {
            look(form);
        }
    });
})();
