const OMNIVA_M = {
    omnivaModule: null,
    currentCountry: null,
    loadedTerminals: [],
    checkoutModule: null,
    selectedTerminal: null,
    leafletJsCdn: {
        src: "https://unpkg.com/leaflet@1.7.1/dist/leaflet.js",
        integrity: 'sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA==',
        crossOrigin: ''
    },
    leafletCssCdn: {
        src: "https://unpkg.com/leaflet@1.7.1/dist/leaflet.css",
        integrity: 'sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A==',
        crossOrigin: ''
    },
    txtPrefix : '',

    observe: function () {
        const targetNode = document.body;

        const config = { attributes: false, childList: true, subtree: true };

        const callback = function (mutationsList, observer) {
            if (
                document.querySelector('input[value^="omniva_m.terminal_"]')
                && !document.querySelector('input[value^="omniva_m.terminal_"][data-initialized="omniva_m"]')
            ) {
                console.log('Omniva_m initializing terminals');
                OMNIVA_M.detectCheckout();
                document.querySelector('input[value^="omniva_m.terminal_"]').dataset.initialized = 'omniva_m';
                OMNIVA_M.init();
            }
        };

        const observer = new MutationObserver(callback);
        observer.observe(targetNode, config);
    },

    detectCheckout: function () {
        if (window.qc && (window.qc.PaymentMethod || window.qc.ShippingMethod)) {
            OMNIVA_M.checkoutModule = 'QcdAjax';
            return;
        }

        if (window._QuickCheckout || window._QuickCheckoutData) {
            OMNIVA_M.checkoutModule = "Journal3";
            return;
        }

        if (document.getElementById('quickcheckoutconfirm')) {
            OMNIVA_M.checkoutModule = "Cqc";
            return;
        }

        if (document.getElementById('onepcheckout')) {
            OMNIVA_M.checkoutModule = "Onepc";
            return;
        }
    },

    init: function () {
        if (typeof omniva_m_status === 'undefined') {
            this.loadCheckoutSettings();
            return;
        }

        if (typeof omniva_m_status !== 'boolean' || !omniva_m_status) {
            return;
        }

        if (typeof omniva_m_country_code !== 'undefined' && omniva_m_country_code == 'FI') {
            this.txtPrefix = 'mh_';
        }

        console.log('Omniva_m starting');
        this.loadLeaflet(this.loadTerminals);
        // this.loadTerminals();
    },

    loadTerminals: function () {
        if (OMNIVA_M.currentCountry === omniva_m_country_code && OMNIVA_M.loadedTerminals.length !== 0) {
            console.log('[ OMNIVA_M ] Terminals loaded from cache');
            OMNIVA_M.generateJsMap(OMNIVA_M.loadedTerminals);
            return;
        }

        fetch(omniva_m_ajax_url + '&action=getTerminals&country_code=' + omniva_m_country_code)
            .then(res => res.json())
            .then(json => {
                if (!json.data) {
                    console.warning('Omniva_m: Could not load terminals');
                    return;
                }

                OMNIVA_M.currentCountry = omniva_m_country_code;
                OMNIVA_M.loadedTerminals = json.data;
                OMNIVA_M.generateJsMap(json.data);
            });
    },

    loadCheckoutSettings: function () {
        fetch('index.php?route=extension/module/omniva_m/ajax&action=getCheckoutSettings')
            .then(res => res.json())
            .then(json => {
                if (!json.data) {
                    console.warning('Omniva_m: Could not load settings');
                    return;
                }

                Object.keys(json.data).forEach(key => {
                    window[key] = json.data[key];
                });
                console.log('Omniva_m settings loaded');
                OMNIVA_M.init();
            });
    },

    generateJsMap: function (terminals) {
        // hide generated radio buttons for terminals except first one
        let inputs = document.querySelectorAll('input[value^="omniva_m.terminal_"]');
        if (inputs.length <= 0) {
            return;
        }

        // here we can try to determine the type of checkout used, for now asume basic opencart 3.0 checkout
        let newInput = null;
        if (OMNIVA_M.checkoutModule && typeof OMNIVA_M[`buildFor${OMNIVA_M.checkoutModule}`] === 'function') {
            newInput = OMNIVA_M[`buildFor${OMNIVA_M.checkoutModule}`](inputs);
            if (typeof OMNIVA_M[`validate${OMNIVA_M.checkoutModule}`] === 'function') {
                OMNIVA_M[`validate${OMNIVA_M.checkoutModule}`]();
            }
        } else {
            newInput = this.buildForBasicOpencart3_0(inputs);
        }

        let mapTranslations = omniva_m_js_translation;
        if (this.txtPrefix != '') {
            mapTranslations.modal_header = omniva_m_js_translation[this.txtPrefix + 'modal_header'];
        }

        let marker_img = 'sasi.png';
        if (omniva_m_country_code == 'FI') {
            marker_img = 'sasi_matkahuolto.svg';
        }

        this.omnivaModule = $(newInput).omniva({
            country_code: omniva_m_country_code,
            path_to_img: 'image/catalog/omniva_m/',
            marker_img: marker_img,
            callback: function (id, manual) {
                OMNIVA_M.omnivaModule.val('omniva_m.terminal_' + id);
                OMNIVA_M.selectedTerminal = id;
                if (manual) {
                    OMNIVA_M.omnivaModule[0].checked = true;
                }

                if (OMNIVA_M.checkoutModule && typeof OMNIVA_M[`handleSelection${OMNIVA_M.checkoutModule}`] === 'function') {
                    OMNIVA_M[`handleSelection${OMNIVA_M.checkoutModule}`](manual);
                }
            },
            translate: mapTranslations,
            terminals: terminals,
        });

        let selected_terminal = document.querySelector('input[value^="omniva_m.terminal_"]:checked');

        if (this.omnivaModule && selected_terminal) {
            let terminal_id = selected_terminal.value.replace('omniva_m.terminal_', '');
            this.omnivaModule.trigger('omniva.select_terminal', terminal_id);
            // since it was selected lets not forget to select our radio
            this.omnivaModule[0].checked = true;
            this.selectedTerminal = terminal_id;
        }

        if (this.omnivaModule && !selected_terminal && this.selectedTerminal) {
            this.omnivaModule.trigger('omniva.select_terminal', this.selectedTerminal);
        }

        if (OMNIVA_M.checkoutModule && typeof OMNIVA_M[`listeners${OMNIVA_M.checkoutModule}`] === 'function') {
            OMNIVA_M[`listeners${OMNIVA_M.checkoutModule}`]();
        }
    },

    buildForBasicOpencart3_0: function (inputs) {
        // hide all options except first
        inputs.forEach((el, index) => {
            if (index === 0) { return; }
            el.closest('.radio').classList.add('hidden');
        });

        let newNode = document.createElement("label");

        newNode.innerHTML = `
            <input type="radio" name="shipping_method" value="">
            ${omniva_m_js_translation[this.txtPrefix + 'shipping_method_terminal']} - ${omniva_m_terminal_price}
        `;

        let refNode = inputs[0].closest('label');
        let parentEl = refNode.parentNode;

        parentEl.insertBefore(newNode, refNode);

        // hide refNode
        refNode.classList.add('hidden');
        return newNode.querySelector('input');
    },

    // Leaflet loading
    loadLeaflet: function (callback) {
        if (typeof L !== "undefined") {
            console.info('[ OMNIVA_M ] Found Leaflet version:', L.version);
            if (typeof callback === 'function') {
                callback();
            }

            return;
        }

        console.info('[ OMNIVA_M ] Loading Leaflet');
        this.loadScript(this.leafletJsCdn, callback);
        this.loadCSS(this.leafletCssCdn);
    },

    makeIdFromUrl: function (url) {
        return url.split('/').pop().replace(/\./gi, '-').toLowerCase();
    },

    loadScript: function (urlData, callback) {
        let script_id = this.makeIdFromUrl(urlData.src);

        if (document.getElementById(script_id)) {
            return;
        }

        let script = document.createElement("script");
        script.type = "text/javascript";
        script.id = script_id;

        if (script.readyState) {  //IE
            script.onreadystatechange = function () {
                if (script.readyState == "loaded" ||
                    script.readyState == "complete") {
                    script.onreadystatechange = null;
                    callback();
                }
            };
        } else {  //Others
            script.onload = function () {
                callback();
            };
        }

        script.src = urlData.src;
        script.integrity = urlData.integrity;
        script.crossOrigin = urlData.crossOrigin;
        document.getElementsByTagName("body")[0].appendChild(script);
    },

    loadCSS: function (urlData) {
        let cssId = this.makeIdFromUrl(urlData.src);
        if (document.getElementById(cssId)) {
            return;
        }
        let head = document.getElementsByTagName('head')[0];
        let link = document.createElement('link');
        link.id = cssId;
        link.rel = 'stylesheet';
        link.type = 'text/css';
        link.href = urlData.src;
        link.integrity = urlData.integrity;
        link.crossOrigin = urlData.crossOrigin;
        link.media = 'all';
        head.appendChild(link);
    },

    /**
     * d_quickcheckout (ajax version?) for QcdAjax
     */
    buildForQcdAjax: function (inputs) {
        // hide all options except second
        let refNode = null;
        let firstEl = null;
        inputs.forEach((el, index) => {
            refNode = el.closest('.radio-input');
            if (index === 1) {
                firstEl = el;

                firstEl.id = 'omniva_m.terminals';
                firstEl.value = '';
                firstEl.dataset.initialized = 'omniva_m';
                refNode.querySelectorAll('label').forEach((label) => {
                    label.attributes.for.value = 'omniva_m.terminals';
                    label.querySelectorAll('span.text').forEach(span => {
                        span.innerText = omniva_m_js_translation[this.txtPrefix + 'shipping_option_title'];
                    });
                });
                refNode.classList.remove('hidden');
                return;
            }

            refNode.classList.add('hidden');
        });

        return firstEl;
    },

    validateQcdAjax: function () {
        /* handles validation on main checkout button presss */
        function _onAjaxReq(options, originalOptions, jqXhr) {
            if (/d_quickcheckout\/confirm/i.test(options.url) && (options.dataType == "json" || !options.dataType)) {
                const selected_node = $("input[name=\"shipping_method\"]:checked");
                if (selected_node.length && selected_node.attr('id').startsWith('omniva_m.terminals') && (!selected_node.val() || selected_node.val() == '0')) {
                    jqXhr.abort();
                    if (omniva_m_js_translation && omniva_m_js_translation[this.txtPrefix + 'select_option_warning']) {
                        alert(omniva_m_js_translation[this.txtPrefix + 'select_option_warning']);
                    } else {
                        alert('Please select Omniva parcel terminal!');
                    }
                    $("html, body").animate({ scrollTop: $('#shipping_method').offset().top }, 1e3);
                    preloaderStop();
                    return false
                }
            }
        }

        $.ajaxPrefilter(_onAjaxReq);
    },

    handleSelectionQcdAjax: function (manual) {
        if (!manual) {
            return;
        }

        OMNIVA_M.omnivaModule.trigger('change');
    },

    /**
     * Custom QuickCheckout - Cqc
     */
    buildForCqc: function (inputs) {
        // hide all options except second
        let refNode = null;
        let firstEl = null;
        inputs.forEach((el, index) => {
            refNode = el.closest('div');
            if (index === 1) {
                firstEl = el;

                firstEl.id = 'omniva_m.terminals';
                firstEl.value = '';
                firstEl.dataset.initialized = 'omniva_m';
                refNode.querySelectorAll('label').forEach((label, lIndex) => {
                    label.attributes.for.value = 'omniva_m.terminals';
                    if (lIndex === 0) {
                        label.lastChild.textContent = label.lastChild.textContent.replaceAll(/.+\]/ig, omniva_m_js_translation[this.txtPrefix + 'shipping_option_title'])
                    }
                });
                refNode.classList.remove('hidden');
                return;
            }

            refNode.classList.add('hidden');
        });
        return firstEl;
    },

    validateCqc: function () {
        if (OMNIVA_M.isValidateCqc) {
            console.log('isValidateCqc registered');
            return;
        }

        /* handles validation on main checkout button presss */
        function _onAjaxReq(options, originalOptions, jqXhr) {
            if (/terms\/validate/i.test(options.url) && (options.dataType == "json" || !options.dataType)) {
                const selected_node = $("input[name=\"shipping_method\"]:checked");
                if (selected_node.length && selected_node.attr('id').startsWith('omniva_m.terminals') && (!selected_node.val() || selected_node.val() == '0')) {
                    jqXhr.abort();
                    if (omniva_m_js_translation && omniva_m_js_translation[this.txtPrefix + 'select_option_warning']) {
                        alert(omniva_m_js_translation[this.txtPrefix + 'select_option_warning']);
                    } else {
                        alert('Please select Omniva parcel terminal!');
                    }
                    $("html, body").animate({ scrollTop: $('#shipping_method').offset().top }, 1e3);
                    $("#button-payment-method").prop("disabled", false);
                    $("#button-payment-method").button("reset");
                    $(".fa-spinner").remove();
                    return false;
                }
            }
        }

        $.ajaxPrefilter(_onAjaxReq);

        OMNIVA_M.isValidateCqc = true;
    },

    handleSelectionCqc: function (manual) {
        if (!manual) {
            return;
        }

        OMNIVA_M.omnivaModule.trigger('change');
    },

    /**
     * Custom Onepcheckout - Onepc
     */
    handleSelectionOnepc: function (manual) {
        if (!manual) {
            return;
        }

        OMNIVA_M.omnivaModule.trigger('change');
    },

    /**
     * Journal3
     */
    buildForJournal3: function (inputs) {
        // hide all options except second
        const styleEl = document.createElement('style');
        styleEl.innerHTML = `
        div.radio:has([value^="omniva_m.terminal"]:not(#omniva_m\\.terminals)) {
            display:none;
        }
        `;
        document.querySelector('head').append(styleEl);
        let refNode = null;
        let firstEl = null;
        inputs.forEach((el, index) => {
            refNode = el.closest('.radio');
            if (index === 1) {
                let clone = refNode.cloneNode(true);
                firstEl = clone.querySelector('input[name="shipping_method"]');

                firstEl.id = 'omniva_m.terminals';
                firstEl.value = '';
                firstEl.dataset.initialized = 'omniva_m';
                clone.querySelectorAll('label').forEach((label) => {
                    label.querySelectorAll('span.shipping-quote-title').forEach(span => {
                        span.lastChild.textContent = span.lastChild.textContent.replaceAll(/.+\]/ig, omniva_m_js_translation[this.txtPrefix + 'shipping_option_title'])
                    });
                });

                clone.style.display = 'flex';
                clone.style.flexDirection = 'column';

                refNode.parentNode.insertBefore(clone, refNode);

                return;
            }
        });

        return firstEl;
    },

    validateJournal3: function () {
        if (OMNIVA_M.isValidateJournal3) {
            console.log('isValidateJournal3 registered');
            return;
        }
        /* handles validation on main checkout button presss */
        function _onAjaxReq(options, originalOptions, jqXhr) {
            if (/checkout\/save\&confirm=true/.test(options.url) && (options.dataType == "json" || !options.dataType)) {
                const selected_node = $("input[name=\"shipping_method\"]:checked");
                if (selected_node.length && selected_node.attr('id') && selected_node.attr('id').startsWith('omniva_m.terminals') && (!selected_node.val() || selected_node.val() == '0')) {
                    jqXhr.abort();
                    if (omniva_m_js_translation && omniva_m_js_translation[this.txtPrefix + 'select_option_warning']) {
                        alert(omniva_m_js_translation[this.txtPrefix + 'select_option_warning']);
                    } else {
                        alert('Please select Omniva parcel terminal!');
                    }
                    $("html, body").animate({ scrollTop: $('.shippings').offset().top }, 1e3);
                    $(".journal-loading-overlay").hide();
                    $("#quick-checkout-button-confirm").button("reset");
                    return false
                }
            }
        }

        function _onAjaxComplete(event, xhr, settings) {
            if (/checkout\/save/.test(settings.url) && (settings.dataType == "json" || !settings.dataType)) {
                console.log('Ajax Complete', OMNIVA_M.getShippingCountryJournal3(), settings);
                const countryId = OMNIVA_M.getShippingCountryJournal3();
                if (OMNIVA_M.journalCountryId !== countryId) {
                    if (OMNIVA_M.omnivaModule) {
                        console.log('REMOVING SELECTOR');
                        OMNIVA_M.omnivaModule.closest('.radio').remove();
                        OMNIVA_M.omnivaModule = null;
                    }
                    OMNIVA_M.journalCountryId = countryId;
                    OMNIVA_M.loadCheckoutSettings();
                }

                OMNIVA_M.checkPriceForJournal3();
            }

            if (/checkout\/cart_update/.test(settings.url) && (settings.dataType == "json" || !settings.dataType)) {
                console.log('Ajax Complete', settings);
                OMNIVA_M.checkPriceForJournal3();
            }
        }

        $.ajaxPrefilter(_onAjaxReq);
        $(document).ajaxComplete(_onAjaxComplete);

        OMNIVA_M.isValidateJournal3 = true;
    },

    handleSelectionJournal3: function (manual) {
        if (!manual) {
            return;
        }
        console.log('Selected: ' + OMNIVA_M.selectedTerminal);
        if ( typeof _QuickCheckout.forceLoadingOverlay === 'function' ) {
            _QuickCheckout.forceLoadingOverlay();
        }
        _QuickCheckout.order_data.shipping_code = OMNIVA_M.omnivaModule.val();
    },

    listenersJournal3: function () {
        OMNIVA_M.journalCountryId = OMNIVA_M.getShippingCountryJournal3();
        OMNIVA_M.journalTerminalPrice = OMNIVA_M.getPriceFromJournal3();
        OMNIVA_M.observeJournal3();
    },

    checkPriceForJournal3: function () {
        if (!OMNIVA_M.omnivaModule) {
            return;
        }

        const currentPrice = OMNIVA_M.getPriceFromJournal3();
        if (OMNIVA_M.journalTerminalPrice !== currentPrice) {
            console.log('Price text changed');
            const span = OMNIVA_M.omnivaModule.closest('.radio').find('span.shipping-quote-title');
            if (span.length) {
                const updatedText = span.text().replace(OMNIVA_M.journalTerminalPrice, currentPrice);
                span.text(updatedText);
            }
            OMNIVA_M.journalTerminalPrice = currentPrice;
        } else {
            console.log('No price change');
        }
    },

    getPriceFromJournal3: function () {
        let keys = [];
        try {
            keys = Object.keys(_QuickCheckout.shipping_methods.omniva_m.quote);
        } catch (error) {
            return '';
        }

        for (let i = 0; i < keys.length; ++i) {
            if (keys[i] !== 'courier') {
                return _QuickCheckout.shipping_methods.omniva_m.quote[keys[i]].text;
            }
        }

        return '';
    },

    observeJournal3: function () {
        if (OMNIVA_M.isObserveJournal3) {
            console.log('isObserveJournal3 registered');
            return;
        }

        document.addEventListener('change', function (e) {
            if (e.target.matches(`input[name="shipping_method"]#omniva_m\\.terminals`)) {
                console.log(e.target);
                OMNIVA_M.handleSelectionJournal3(true);
            }
        });

        const targetNode = document.body;

        const config = { attributes: true, childList: true, subtree: true };

        const callback = function (mutationsList, observer) {
            const selectedNode = $("input[name=\"shipping_method\"]:checked");
            const omnivaSelector = document.querySelector('#omniva_m\\.terminals');
            if (omnivaSelector && selectedNode.length && selectedNode.val().startsWith('omniva_m.terminal') && !selectedNode.attr('id')) {
                omnivaSelector.checked = true;
                if (omnivaSelector.value !== selectedNode.val()) {
                    omnivaSelector.value = selectedNode.val();
                    let terminal_id = selectedNode.val().replace('omniva_m.terminal_', '');
                    OMNIVA_M.omnivaModule.trigger('omniva.select_terminal', terminal_id);
                }
                console.log(selectedNode.val(), 'moved radio');
            }
        };

        const observer = new MutationObserver(callback);
        observer.observe(targetNode, config);

        OMNIVA_M.isObserveJournal3 = true;
    },

    getShippingCountryJournal3: function () {
        let addressCheckbox = document.querySelector('.checkout-same-address input[type="checkbox"]');
        if ( ! addressCheckbox ) {
            addressCheckbox = document.querySelector('.checkout-section.payment-address input[type="checkbox"]');
        }
        if (addressCheckbox.checked) {
            return document.querySelector('select#input-payment-country').value;
        }

        return document.querySelector('select#input-shipping-country').value;
    },
};

document.addEventListener('DOMContentLoaded', function (e) {
    OMNIVA_M.observe();
});
