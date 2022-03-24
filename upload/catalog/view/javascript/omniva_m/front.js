const OMNIVA_M = {
    omnivaModule: null,
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

    observe: function () {
        const targetNode = document.body;

        const config = { attributes: false, childList: true, subtree: true };

        const callback = function (mutationsList, observer) {
            if (document.querySelector('input[value^="omniva_m.terminal_"]')) {
                OMNIVA_M.init();
                observer.disconnect();
                console.log('Omniva_m terminal found - disconecting observer');
            }
        };

        const observer = new MutationObserver(callback);
        observer.observe(targetNode, config);
    },

    init: function () {
        if (typeof omniva_m_status !== 'boolean' || !omniva_m_status) {
            return;
        }

        console.log('Omniva_m starting');
        this.loadLeaflet(this.loadTerminals);
        // this.loadTerminals();
    },

    loadTerminals: function () {
        fetch(omniva_m_ajax_url + '&action=getTerminals&country_code=' + omniva_m_country_code)
            .then(res => res.json())
            .then(json => {
                if (!json.data) {
                    console.warning('Omniva_m: Could not load terminals');
                    return;
                }

                OMNIVA_M.generateJsMap(json.data);
            });
    },

    generateJsMap: function (terminals) {
        // hide generated radio buttons for terminals except first one
        let inputs = document.querySelectorAll('input[value^="omniva_m.terminal_"]');
        if (inputs.length <= 0) {
            return;
        }

        // here we can try to determine the type of checkout used, for now asume basic opencart 3.0 checkout
        let newInput = this.buildForBasicOpencart3_0(inputs);

        this.omnivaModule = $(newInput).omniva({
            country_code: omniva_m_country_code,
            path_to_img: 'image/catalog/omniva_m/',
            callback: function (id, manual) {
                OMNIVA_M.omnivaModule.val('omniva_m.terminal_' + id);
                if (manual) {
                    OMNIVA_M.omnivaModule[0].checked = true;
                }
            },
            translations: omniva_m_js_translation,
            terminals: terminals,
        });

        let selected_terminal = document.querySelector('input[value^="omniva_m.terminal_"]:checked');

        if (this.omnivaModule && selected_terminal) {
            let terminal_id = selected_terminal.value.replace('omniva_m.terminal_', '');
            this.omnivaModule.trigger('omniva.select_terminal', terminal_id);
            // since it was selected lets not forget to select our radio
            this.omnivaModule[0].checked = true;
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
            ${omniva_m_js_translation.shipping_method_terminal} - ${omniva_m_terminal_price}
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
    }
};

document.addEventListener('DOMContentLoaded', function (e) {
    OMNIVA_M.observe();
});