(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var editor = document.getElementById('ihn_summary_editor');
        var source = document.getElementById('ihn_samenvatting');
        var toolbar = document.querySelector('.ihn-summary-editor-toolbar');
        var savedRange = null;
        var legacyPaletteMigrations = [
            { legacy: 'ihn-samenvatting-kleur-oceaan', text: 'wit', background: 'marine' },
            { legacy: 'ihn-samenvatting-kleur-koraal', text: 'inkt', background: 'kaart' },
            { legacy: 'ihn-samenvatting-kleur-bos', text: 'accent', background: 'pagina' },
            { legacy: 'ihn-samenvatting-kleur-bes', text: 'wit', background: 'inkt' }
        ];
        var legacyPaletteClasses = legacyPaletteMigrations.map(function (migration) {
            return migration.legacy;
        });
        var textColorKeys = ['inkt', 'marine', 'accent', 'wit'];
        var backgroundColorKeys = ['marine', 'kaart', 'pagina', 'inkt'];
        var textColorClasses = textColorKeys.map(function (key) {
            return 'ihn-samenvatting-tekst-' + key;
        });
        var backgroundColorClasses = backgroundColorKeys.map(function (key) {
            return 'ihn-samenvatting-achtergrond-' + key;
        });

        if (!editor || !source || !toolbar) {
            return;
        }

        function syncSource() {
            source.value = editor.innerHTML;
        }

        function getEditorRange() {
            var selection = window.getSelection();
            if (selection && selection.rangeCount > 0 && editor.contains(selection.anchorNode)) {
                savedRange = selection.getRangeAt(0).cloneRange();
                return selection.getRangeAt(0);
            }

            if (!savedRange) {
                return null;
            }

            editor.focus();
            selection.removeAllRanges();
            selection.addRange(savedRange);
            return selection.getRangeAt(0);
        }

        function selectWrapper(wrapper) {
            var selection = window.getSelection();
            var range = document.createRange();
            range.selectNodeContents(wrapper);
            selection.removeAllRanges();
            selection.addRange(range);
            savedRange = range.cloneRange();
        }

        function wrapSelection(wrapper) {
            var range = getEditorRange();
            if (!range || range.collapsed) {
                return false;
            }

            try {
                range.surroundContents(wrapper);
            } catch (error) {
                wrapper.appendChild(range.extractContents());
                range.insertNode(wrapper);
            }

            selectWrapper(wrapper);
            return true;
        }

        function getColorWrapper(range) {
            var node = range.startContainer.nodeType === Node.ELEMENT_NODE
                ? range.startContainer
                : range.startContainer.parentElement;

            while (node && node !== editor) {
                if (node.nodeType === Node.ELEMENT_NODE && node.tagName === 'SPAN'
                    && legacyPaletteClasses.concat(textColorClasses, backgroundColorClasses).some(function (className) {
                        return node.classList.contains(className);
                    })) {
                    return node;
                }
                node = node.parentElement;
            }

            return null;
        }

        function migrateLegacyPalette(wrapper) {
            legacyPaletteMigrations.forEach(function (migration) {
                if (wrapper.classList.contains(migration.legacy)) {
                    wrapper.classList.remove(migration.legacy);
                    wrapper.classList.add(
                        'ihn-samenvatting-tekst-' + migration.text,
                        'ihn-samenvatting-achtergrond-' + migration.background
                    );
                }
            });
        }

        function applyColor(className, kind) {
            var range = getEditorRange();
            if (!range || range.collapsed) {
                return;
            }

            var existingWrapper = getColorWrapper(range);
            if (existingWrapper) {
                migrateLegacyPalette(existingWrapper);
                var classesToReplace = kind === 'text' ? textColorClasses : backgroundColorClasses;
                classesToReplace.forEach(function (existingClass) {
                    existingWrapper.classList.remove(existingClass);
                });
                existingWrapper.classList.add(className);
                selectWrapper(existingWrapper);
                return;
            }

            var color = document.createElement('span');
            color.className = className;
            wrapSelection(color);
        }

        function unwrap(element) {
            while (element.firstChild) {
                element.parentNode.insertBefore(element.firstChild, element);
            }
            element.remove();
        }

        function clearFormatting(node) {
            Array.prototype.slice.call(node.childNodes).forEach(function (child) {
                if (child.nodeType !== Node.ELEMENT_NODE) {
                    return;
                }

                clearFormatting(child);

                if (/^(B|STRONG|I|EM|U|S|STRIKE|DEL|SUP|SUB|FONT)$/.test(child.tagName)) {
                    unwrap(child);
                    return;
                }

                if (child.tagName === 'SPAN') {
                    legacyPaletteClasses.concat(textColorClasses, backgroundColorClasses).forEach(function (className) {
                        child.classList.remove(className);
                    });
                    child.removeAttribute('data-ihn-baseline-shift');
                    child.removeAttribute('data-ihn-font-size');
                    ['font-size', 'vertical-align', 'color', 'background-color'].forEach(function (property) {
                        child.style.removeProperty(property);
                    });

                    if (!child.className) {
                        child.removeAttribute('class');
                    }
                    if (!child.style.length) {
                        child.removeAttribute('style');
                    }

                    if (!child.attributes.length) {
                        unwrap(child);
                    }
                }
            });
        }

        function resetSelectionFormatting() {
            var range = getEditorRange();
            if (!range || range.collapsed) {
                return;
            }

            var node = range.startContainer.nodeType === Node.ELEMENT_NODE
                ? range.startContainer
                : range.startContainer.parentElement;
            while (node && node !== editor) {
                if (node.nodeType === Node.ELEMENT_NODE
                    && /^(B|STRONG|I|EM|U|S|STRIKE|DEL|SUP|SUB|FONT|SPAN)$/.test(node.tagName)) {
                    var nodeRange = document.createRange();
                    nodeRange.selectNodeContents(node);
                    if (range.compareBoundaryPoints(Range.START_TO_START, nodeRange) === 0
                        && range.compareBoundaryPoints(Range.END_TO_END, nodeRange) === 0) {
                        range.selectNode(node);
                    }
                }
                node = node.parentElement;
            }

            var fragment = range.extractContents();
            clearFormatting(fragment);
            range.insertNode(fragment);
        }

        function applyStyle(property, value, dataAttribute) {
            var range = getEditorRange();
            if (!range || range.collapsed) {
                return;
            }

            var wrapper = document.createElement('span');
            wrapper.style.setProperty(property, value);
            wrapper.setAttribute(dataAttribute, value);
            wrapSelection(wrapper);
        }

        toolbar.addEventListener('mousedown', function (event) {
            if (event.target.closest('button')) {
                getEditorRange();
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
            var colorKind = button.getAttribute('data-color-kind');
            var baselineShift = button.getAttribute('data-baseline-shift');
            if (command) {
                getEditorRange();
                document.execCommand(command, false, null);
            } else if (colorClass) {
                applyColor(colorClass, colorKind);
            } else if (baselineShift) {
                applyStyle('vertical-align', baselineShift, 'data-ihn-baseline-shift');
            } else if (button.hasAttribute('data-reset-format')) {
                resetSelectionFormatting();
            }

            syncSource();
        });

        toolbar.addEventListener('change', function (event) {
            if (event.target.hasAttribute('data-font-size')) {
                if (event.target.value) {
                    applyStyle('font-size', event.target.value, 'data-ihn-font-size');
                }
                event.target.value = '';
                syncSource();
            }
        });

        document.addEventListener('selectionchange', function () {
            var selection = window.getSelection();
            if (selection && selection.rangeCount > 0 && editor.contains(selection.anchorNode)) {
                savedRange = selection.getRangeAt(0).cloneRange();
            }
        });

        editor.addEventListener('input', syncSource);
        var form = editor.closest('form');
        if (form) {
            form.addEventListener('submit', syncSource, true);
        }
    });
})();