document.addEventListener('click', async function (event) {
    var button = event.target.closest('[data-share-image]');
    if (!button) {
        return;
    }

    var status = button.closest('.ihn-story-sharing').querySelector('.ihn-story-sharing-status');

    try {
        var story = JSON.parse(button.getAttribute('data-share-image'));
        var blob = await createStoryImage(story);

        if (navigator.clipboard && window.ClipboardItem && window.isSecureContext) {
            try {
                await navigator.clipboard.write([
                    new ClipboardItem({ 'image/png': blob })
                ]);
                status.textContent = 'De afbeelding is gekopieerd. Plak hem in Instagram om te posten.';
                return;
            } catch (error) {
                downloadStoryImage(blob);
                status.textContent = 'Deze browser kan geen afbeeldingen kopiëren. Een afbeelding is gedownload om in Instagram te posten over dit verhaal.';
                return;
            }
        }

        downloadStoryImage(blob);
        status.textContent = 'Deze browser kan geen afbeeldingen kopiëren. Een afbeelding is gedownload om in Instagram te posten over dit verhaal.';
    } catch (error) {
        status.textContent = 'De Instagram-afbeelding maken is niet gelukt.';
    }
});

async function createStoryImage(story) {
    await document.fonts.ready;

    var canvas = document.createElement('canvas');
    var context = canvas.getContext('2d');
    if (!context) {
        throw new Error('Canvas is not available');
    }

    canvas.width = 1080;
    canvas.height = 1080;

    var rootStyle = getComputedStyle(document.documentElement);
    var pageBackground = rootStyle.getPropertyValue('--color-bg').trim() || '#f0ece4';
    var cardBackground = rootStyle.getPropertyValue('--color-card-bg').trim() || '#ebe6dc';
    var textColor = rootStyle.getPropertyValue('--color-text').trim() || '#1c1c1c';
    var accentColor = rootStyle.getPropertyValue('--color-accent').trim() || '#2f4a7a';
    var sansFont = getComputedStyle(document.querySelector('.wp-block-post-content')).fontFamily;
    var serifFont = getComputedStyle(document.querySelector('.wp-block-post-title')).fontFamily;
    var inset = 24;
    var padding = 84;
    var contentWidth = canvas.width - 2 * (inset + padding);

    context.fillStyle = pageBackground;
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.fillStyle = cardBackground;
    context.beginPath();
    roundedRect(context, inset, inset, canvas.width - 2 * inset, canvas.height - 2 * inset, 80);
    context.fill();

    var fontSize = 44;
    var summaryLines;
    var titleLines;
    var summaryLineHeight;
    var titleLineHeight;
    var summaryFont;
    var titleFont;
    var titlePrefixWidth;

    do {
        summaryFont = fontSize + 'px ' + sansFont;
        titleFont = 'italic ' + Math.round(fontSize * 0.78) + 'px ' + serifFont;
        summaryLineHeight = fontSize * 1.75;
        titleLineHeight = fontSize * 0.78 * 1.5;

        context.font = summaryFont;
        summaryLines = wrapText(context, story.summary, contentWidth);
        context.font = titleFont;
        titlePrefixWidth = context.measureText('Lees ').width;
        titleLines = wrapText(context, story.title, contentWidth, titlePrefixWidth);

        fontSize -= 2;
    } while (fontSize >= 32 &&
        summaryLines.length * summaryLineHeight +
        titleLines.length * titleLineHeight + 40 >
        canvas.height - 2 * (inset + padding));

    context.fillStyle = textColor;
    context.font = summaryFont;
    context.textBaseline = 'top';
    summaryLines.forEach(function (line, index) {
        context.fillText(line, inset + padding, inset + padding + index * summaryLineHeight);
    });

    var titleY = canvas.height - inset - padding - titleLines.length * titleLineHeight;
    context.font = titleFont;
    context.fillStyle = textColor;
    context.fillText('Lees ', inset + padding, titleY);
    context.fillStyle = accentColor;
    titleLines.forEach(function (line, index) {
        var lineX = index === 0 ? inset + padding + titlePrefixWidth : inset + padding;
        context.fillText(line, lineX, titleY + index * titleLineHeight);
    });

    return new Promise(function (resolve, reject) {
        canvas.toBlob(function (blob) {
            if (blob) {
                resolve(blob);
            } else {
                reject(new Error('Could not create image'));
            }
        }, 'image/png');
    });
}

function wrapText(context, text, maxWidth, firstLineOffset) {
    var lines = [];
    var availableWidth = maxWidth - (firstLineOffset || 0);

    String(text).split('\n').forEach(function (paragraph) {
        var words = paragraph.split(/\s+/).filter(Boolean);
        var line = '';

        words.forEach(function (word) {
            var candidate = line ? line + ' ' + word : word;
            if (line && context.measureText(candidate).width > availableWidth) {
                lines.push(line);
                line = word;
                availableWidth = maxWidth;
            } else {
                line = candidate;
            }
        });

        lines.push(line);
        availableWidth = maxWidth;
    });

    return lines;
}

function downloadStoryImage(blob) {
    var link = document.createElement('a');
    var url = URL.createObjectURL(blob);
    link.href = url;
    link.download = 'in-het-nauw-verhaal.png';
    link.click();
    window.setTimeout(function () {
        URL.revokeObjectURL(url);
    }, 1000);
}

function roundedRect(context, x, y, width, height, radius) {
    context.moveTo(x + radius, y);
    context.lineTo(x + width - radius, y);
    context.quadraticCurveTo(x + width, y, x + width, y + radius);
    context.lineTo(x + width, y + height - radius);
    context.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
    context.lineTo(x + radius, y + height);
    context.quadraticCurveTo(x, y + height, x, y + height - radius);
    context.lineTo(x, y + radius);
    context.quadraticCurveTo(x, y, x + radius, y);
    context.closePath();
}
