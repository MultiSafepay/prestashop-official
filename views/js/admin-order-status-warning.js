(function () {
    function onReady(callback)
    {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }
        callback();
    }

    function toStr(value)
    {
        return value === null || value === undefined ? '' : String(value);
    }

    onReady(function () {
        const cfg = window.mspCaptureWarning;
        if (!cfg || !cfg.enabled) {
            return;
        }

        const authorizedStateId = toStr(cfg.authorizedStateId);
        const originalStateId = toStr(cfg.currentStateId);

        if (!authorizedStateId || originalStateId !== authorizedStateId) {
            return;
        }

        const message = cfg.message ||
            'This order payment is authorized but not captured. If you change the status now, the Capture action will no longer be available until you revert to Authorized. Do you want to continue?';

        function uniqueElements(elements)
        {
            const unique = [];
            const seen = new Set();
            for (let i = 0; i < elements.length; i++) {
                const el = elements[i];
                if (!el || seen.has(el)) {
                    continue;
                }
                seen.add(el);
                unique.push(el);
            }
            return unique;
        }

        function queryAll(selectors)
        {
            const matches = [];
            for (let i = 0; i < selectors.length; i++) {
                const selector = selectors[i];
                const found = document.querySelectorAll(selector);
                for (let j = 0; j < found.length; j++) {
                    matches.push(found[j]);
                }
            }
            return uniqueElements(matches);
        }

        const preferredSelectors = [
            'select#update_order_status_action_input',
            'select#update_order_status_new_order_status_id',
            'select#id_order_state',
            'select[name="id_order_state"]',
            'select[name="update_order_status[new_order_status_id]"]',
        ];

        let stateSelects = queryAll(preferredSelectors);
        if (!stateSelects.length) {
            stateSelects = queryAll([
                'select[name="order_state"]',
                'select[name*="order_state"]',
            ]);
        }

        if (!stateSelects.length) {
            return;
        }

        function shouldWarn(nextValue)
        {
            const next = toStr(nextValue);
            if (!next) {
                return false;
            }

            return originalStateId === authorizedStateId && next !== authorizedStateId;
        }

        function confirmOrRevert(nextValue)
        {
            if (!shouldWarn(nextValue)) {
                return true;
            }

            if (window.confirm(message)) {
                return true;
            }

            return false;
        }

        function bindToSelect(stateSelect)
        {
            let lastConfirmedValue = toStr(stateSelect.value);

            function confirmOrRevertWithElement(nextValue)
            {
                if (!confirmOrRevert(nextValue)) {
                    stateSelect.value = lastConfirmedValue;
                    return false;
                }

                lastConfirmedValue = toStr(nextValue);
                return true;
            }

            stateSelect.addEventListener('change', function () {
                confirmOrRevertWithElement(stateSelect.value);
            });

            const form = stateSelect.closest('form');
            if (!form) {
                return;
            }

            form.addEventListener('submit', function (e) {
                if (!confirmOrRevertWithElement(stateSelect.value)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        }

        for (let i = 0; i < stateSelects.length; i++) {
            bindToSelect(stateSelects[i]);
        }
    });
})();
