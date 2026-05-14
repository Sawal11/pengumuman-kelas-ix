(function () {
    const pageLoader = document.getElementById('pageLoader');

    function showPageLoader() {
        if (pageLoader) {
            pageLoader.classList.remove('is-hidden');
        }
    }

    function hidePageLoader() {
        if (pageLoader) {
            pageLoader.classList.add('is-hidden');
        }
    }

    if (document.readyState === 'complete') {
        window.setTimeout(hidePageLoader, 250);
    } else {
        window.addEventListener('load', () => window.setTimeout(hidePageLoader, 250));
    }
    window.setTimeout(hidePageLoader, 2500);

    document.querySelectorAll('.js-delete-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const confirmed = window.confirm('Yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.');
            if (!confirmed) {
                event.preventDefault();
                hidePageLoader();
                return;
            }
            showPageLoader();
        });
    });

    document.querySelectorAll('form:not(.js-delete-form)').forEach((form) => {
        form.addEventListener('submit', () => {
            showPageLoader();
        });
    });

    document.querySelectorAll('a[href]').forEach((link) => {
        link.addEventListener('click', () => {
            const href = link.getAttribute('href') || '';
            const target = link.getAttribute('target') || '';
            const isDownload = link.hasAttribute('download');
            const isExternal = /^https?:\/\//i.test(href);
            const isUtility = href === '' || href.startsWith('#') || href.startsWith('javascript:');
            const isFileResponse = href.includes('export=csv') || href.includes('template=csv') || href.includes('template=xlsx');

            if (target === '_blank' || isDownload || isExternal || isUtility || isFileResponse) {
                return;
            }

            showPageLoader();
        });
    });
})();
