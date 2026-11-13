(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var editor = document.getElementById('ihn_summary_editor');
        var source = document.getElementById('ihn_samenvatting');
        var toolbar = document.querySelector('.ihn-summary-editor-toolbar');

        if (!editor || !source || !toolbar) {
            return;
        }

        function syncSource() {
            source.value = editor.innerHTML;
        }

        function applyColor(className) {
            var selection = window.getSelection();
            if (!selection || selection.rangeCount === 0 || selection.isCollapsed) {
                return;
            }

            var range = selection.getRangeAt(0);
            var color = document.createElement('span');
            color.className = className;

            try {
                range.surroundContents(color);
            } catch (error) {
                color.appendChild(range.extractContents());
                range.insertNode(color);
            }

            selection.removeAllRanges();
            range.selectNodeContents(color);
            selection.addRange(range);
        }

        toolbar.addEventListener('mousedown', function (event) {
            if (event.target.closest('button')) {
                event.preventDefault();
            }
        });

        toolbar.addEventListener('click', function (event) {
            var button = event.target.closest('button');
            if (!button) {
                return;
            }

            var command = button.getAttribute('data-command');
            var colorClass = button.getAttribute('data-color-class');
            if (command) {
                document.execCommand(command, false, null);
            } else if (colorClass) {
                applyColor(colorClass);
            }

            syncSource();
        });

        editor.addEventListener('input', syncSource);
        var form = editor.closest('form');
        if (form) {
            form.addEventListener('submit', syncSource, true);
        }
    });
})();