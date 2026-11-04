(function () {
    // Fancybox 3 is used by the bundled galleries. Keep the disclosure attached
    // to the image even when the visitor hides the gallery caption/toolbar.
    function install() {
        if (!window.jQuery || window.frootboxAiLightboxInstalled) return;
        window.frootboxAiLightboxInstalled = true;
        window.jQuery(document).on('afterLoad.fb beforeShow.fb', function (event, instance, slide) {
            if (!slide || !slide.$content) return;
            slide.$content.children('[data-frootbox-ai-label]').remove();
            var origin = slide.opts && slide.opts.$orig;
            if (!origin) return;
            var type = origin.attr('data-frootbox-ai');
            if (type !== '1' && type !== 'edited') return;
            var text = type === '1' ? 'KI-generiert' : 'Mit KI bearbeitet';
            window.jQuery('<span class="frootbox-ai-label" data-frootbox-ai-label>' + text + '</span>')
                .appendTo(slide.$content);
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', install, { once: true });
    }
    install();
})();
