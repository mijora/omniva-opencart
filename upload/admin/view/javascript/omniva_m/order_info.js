const OMNIVA_M_ORDER_INFO = {

    refreshNowBtnHtml: `<a href="#" class="btn btn-default omniva_m-refresh-now-btn">${OMNIVA_M_INFO_PANEL_TRANSLATION.refresh_now_btn}</a>`,
    addPackageBtnHtml: `<div class="col-sm-12 text-center omniva_m-add-package-btn-container"><button class="btn btn-default omniva_m-add-package-btn">${OMNIVA_M_INFO_PANEL_TRANSLATION.add_package_btn}</button></div>`,
    packagesContainer: null,

    init: function () {
        this.addOrderInformationPanel();
        this.generatePackages();
        this.showLastDelPackageBtn();
        this.registerListeners();
    },

    registerListeners: function () {
        document.addEventListener('change', function (e) {
            if (e.target.matches('select[name="omniva_m_cod_use"]')) {
                e.preventDefault();

                const multiTypeValue = document.querySelector(`input[name="omniva_m_multi_type"]`);
                if (multiTypeValue) {
                    multiTypeValue.value = 'multiparcel';
                    if (
                        OMNIVA_M_ORDER_DATA.shipping_type !== OMNIVA_M_ORDER_DATA.shipping_types.terminal
                        && e.target.value == '1'
                    ) {
                        multiTypeValue.value = 'consolidate';
                    }
                }
                OMNIVA_M_ORDER_INFO.generatePackages();
                const packages = OMNIVA_M_ORDER_INFO.packagesContainer.querySelectorAll('[data-package]');
                for (let index = 2; index <= packages.length; index++) {
                    OMNIVA_M_ORDER_INFO.delPackage(index);
                }

                return;
            }
        });

        document.addEventListener('click', function (e) {
            if (e.target.matches('.omniva_m-register-label-btn')) {
                e.preventDefault();
                console.log('registering label...');
                if (OMNIVA_M_ORDER_DATA.label_history.last_barcodes && !confirm(OMNIVA_M_INFO_PANEL_TRANSLATION.confirm_new_label)) {
                    return;
                }
                OMNIVA_M_ORDER_INFO.registerLabel();
                return;
            }

            if (e.target.matches('.omniva_m-print-label-btn')) {
                e.preventDefault();
                console.log('printing label...');
                OMNIVA_M_ORDER_INFO.printLabel(null);
                return;
            }

            if (e.target.matches('.omniva_m-print-history-label-btn')) {
                e.preventDefault();
                console.log('printing history label...', e.target.dataset.historyId);
                // return;
                OMNIVA_M_ORDER_INFO.printLabel(e.target.dataset.historyId);
                return;
            }

            if (e.target.matches('.omniva_m-save-data-btn')) {
                e.preventDefault();
                console.log('saving data...');
                OMNIVA_M_ORDER_INFO.saveOrderData();
                return;
            }

            if (e.target.matches('.omniva_m-refresh-now-btn')) {
                e.preventDefault();
                window.location.reload();
                return;
            }

            if (e.target.matches('.omniva_m-add-package-btn')) {
                e.preventDefault();
                OMNIVA_M_ORDER_INFO.addPackage();
                OMNIVA_M_ORDER_INFO.showLastDelPackageBtn();
                return;
            }

            if (e.target.matches('.omniva_m-del-package-btn')) {
                e.preventDefault();
                OMNIVA_M_ORDER_INFO.delPackage(e.target.dataset.packageDel);
                OMNIVA_M_ORDER_INFO.showLastDelPackageBtn();
                return;
            }
        });
    },

    addOrderInformationPanel: function () {
        const historyPanel = document.querySelector('#history').closest('.panel');

        const omnivaPanel = document.querySelector('#omniva_m-panel');

        historyPanel.parentNode.insertBefore(omnivaPanel, historyPanel);
    },

    showOverlay: function (shouldShow) {
        const overlayEl = document.querySelector('.omniva_m-panle-overlay');

        overlayEl.classList[shouldShow ? 'remove' : 'add']('hidden');
    },

    showResponseInfo: function (msg, type) {
        const responseEl = document.querySelector('#omniva_m-response-info');

        responseEl.innerHTML = msg;
        responseEl.classList.remove('hidden', 'alert-success', 'alert-danger', 'alert-warning', 'alert-info');
        responseEl.classList.add(`alert-${type}`);
    },

    saveOrderData: function () {
        const omnivaPanel = document.querySelector('#omniva_m-panel');
        const weight = omnivaPanel.querySelector('#input-omniva_m-total-weight').value;
        const order_id = omnivaPanel.querySelector('input[name="omniva_m_order_id"]').value;

        const data = new FormData();
        data.omniva_m_has_data = false;
        data.append('order_id', order_id);


        if (OMNIVA_M_ORDER_DATA.total_weight != weight) {
            data.omniva_m_has_data = true;
            data.append('weight', weight);
        }

        if (omnivaPanel.querySelector('#input-omniva_m-cod-amount')) {
            const cod_amount = omnivaPanel.querySelector('#input-omniva_m-cod-amount').value;
            const cod_use = omnivaPanel.querySelector('#input-omniva_m-cod-use').value;
            if (OMNIVA_M_ORDER_DATA.cod.oc_amount != cod_amount) {
                data.omniva_m_has_data = true;
                data.append('cod_amount', cod_amount);
            }
    
            if (OMNIVA_M_ORDER_DATA.cod.order_use != cod_use) {
                data.omniva_m_has_data = true;
                data.append('cod_use', cod_use);
            }
        }

        // if there was previoius changes force update (so in case data was restored to original it is removed from order_data)
        if (OMNIVA_M_ORDER_DATA.order_data) {
            data.omniva_m_has_data = true;
        }

        // const data = { weight, cod_amount, cod_use, order_id };

        const packages = omnivaPanel.querySelectorAll('[data-package]');
        if (packages) {
            if (OMNIVA_M_ORDER_DATA.multiparcel != packages.length) {
                data.omniva_m_has_data = true;
                data.append('multiparcel', packages.length);
            }

            let packagesData = [];
            packages.forEach((el, index) => {
                let services = {};
                el.querySelectorAll('input[data-service]:checked').forEach(checkbox => {
                    const serviceKey = checkbox.dataset.service;
                    services[checkbox.dataset.service] = {};
                    if (OMNIVA_M_ORDER_DATA.add_services[serviceKey]) {
                        OMNIVA_M_ORDER_DATA.add_services[serviceKey].forEach(serviceParam => {
                            const serviceParamValue = el.querySelector(`[data-service-value="${serviceKey}_${serviceParam}"]`);
                            if (serviceParamValue) {
                                services[checkbox.dataset.service][serviceParam] = serviceParamValue.value;
                            }
                        });
                    }
                });
                packagesData.push(services);
            });

            data.append('packages', btoa(JSON.stringify(packagesData)));
        }

        if (!data.omniva_m_has_data) {
            this.showResponseInfo(OMNIVA_M_INFO_PANEL_TRANSLATION.no_data_changes, 'success');
            return;
        }
        OMNIVA_M_ORDER_INFO.showOverlay(true);
        fetch(OMNIVA_M_ORDER_DATA.ajax_url + '&action=saveOrderData', {
            method: 'POST',
            body: data
        })
            .then(res => res.json())
            .then(json => {
                if (typeof json.data === 'undefined') {
                    OMNIVA_M_ORDER_INFO.showResponseInfo(OMNIVA_M_INFO_PANEL_TRANSLATION.bad_response, 'warning');
                    return;
                }

                if (typeof json.data.error !== 'undefined') {
                    OMNIVA_M_ORDER_INFO.showResponseInfo(json.data.error, 'danger');
                    return;
                }

                if (json.data === false) {
                    OMNIVA_M_ORDER_INFO.showResponseInfo(OMNIVA_M_INFO_PANEL_TRANSLATION.order_not_saved, 'danger');
                    return;
                }

                OMNIVA_M_ORDER_INFO.showResponseInfo(
                    OMNIVA_M_INFO_PANEL_TRANSLATION.order_saved + OMNIVA_M_ORDER_INFO.refreshNowBtnHtml,
                    'success'
                );
                setTimeout(() => {
                    window.location.reload();
                }, 5000);
            })
            .finally(() => {
                OMNIVA_M_ORDER_INFO.showOverlay(false);
            });
    },

    registerLabel: function () {
        const omnivaPanel = document.querySelector('#omniva_m-panel');
        const order_id = omnivaPanel.querySelector('input[name="omniva_m_order_id"]').value;

        data = new FormData();
        data.append('order_id', order_id);

        OMNIVA_M_ORDER_INFO.showOverlay(true);
        fetch(OMNIVA_M_ORDER_DATA.ajax_url + '&action=registerLabel', {
            method: 'POST',
            body: data
        })
            .then(res => res.json())
            .then(json => {
                console.log(json);

                if (!json.data && json.data !== false) {
                    OMNIVA_M_ORDER_INFO.showResponseInfo(OMNIVA_M_INFO_PANEL_TRANSLATION.bad_response, 'warning');
                    return;
                }

                if (typeof json.data.error !== 'undefined') {
                    OMNIVA_M_ORDER_INFO.showResponseInfo(json.data.error, 'danger');
                    return;
                }


                OMNIVA_M_ORDER_INFO.showResponseInfo(
                    OMNIVA_M_INFO_PANEL_TRANSLATION.label_registered + OMNIVA_M_ORDER_INFO.refreshNowBtnHtml,
                    'success');
                setTimeout(() => {
                    window.location.reload();
                }, 5000);
            })
            .finally(() => {
                OMNIVA_M_ORDER_INFO.showOverlay(false);
            });
    },

    printLabel: function (history_id) {
        const omnivaPanel = document.querySelector('#omniva_m-panel');
        const order_id = omnivaPanel.querySelector('input[name="omniva_m_order_id"]').value;

        data = new FormData();
        data.append('order_ids[]', order_id);

        if (history_id) {
            data.append('history_id', history_id);
        }

        OMNIVA_M_ORDER_INFO.showOverlay(true);
        fetch(OMNIVA_M_ORDER_DATA.ajax_url + '&action=printLabel', {
            method: 'POST',
            body: data
        })
            .then(res => res.json())
            .then(json => {
                console.log(json);

                if (!json.data && json.data !== false) {
                    OMNIVA_M_ORDER_INFO.showResponseInfo(OMNIVA_M_INFO_PANEL_TRANSLATION.bad_response, 'warning');
                    return;
                }

                if (typeof json.data.error !== 'undefined') {
                    OMNIVA_M_ORDER_INFO.showResponseInfo(json.data.error, 'danger');
                    return;
                }

                OMNIVA_M_ORDER_INFO.downloadPdf(json.data.pdf);
            })
            .finally(() => {
                OMNIVA_M_ORDER_INFO.showOverlay(false);
            });
    },

    downloadPdf: function (data) {
        const pdfContent = `data:application/pdf;base64,${data}`;

        var encodedUri = encodeURI(pdfContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "omniva_labels.pdf");
        document.body.appendChild(link); // Required for FF

        link.click(); // This will download the data file

        link.remove();
    },

    generatePackages: function() {
        OMNIVA_M_ORDER_INFO.packagesContainer = document.querySelector('.omniva_m-packages-container');
        
        if (!OMNIVA_M_ORDER_INFO.packagesContainer) {
            return;
        }

        OMNIVA_M_ORDER_INFO.packagesContainer.innerHTML = OMNIVA_M_ORDER_INFO.addPackageBtnHtml;
        
        const codUsage = document.querySelector(`select[name="omniva_m_cod_use"]`)?.value;

        if (OMNIVA_M_ORDER_DATA.shipping_type === OMNIVA_M_ORDER_DATA.shipping_types.terminal && codUsage == '1') {
            OMNIVA_M_ORDER_INFO.packagesContainer.innerHTML = '';
        }

        const btnContainer = OMNIVA_M_ORDER_INFO.packagesContainer.querySelector('.omniva_m-add-package-btn-container');

        OMNIVA_M_ORDER_INFO.packagesContainer.insertBefore(OMNIVA_M_ORDER_INFO.generatePacakgeHtml(1), btnContainer);

        if (OMNIVA_M_ORDER_DATA.order_data && OMNIVA_M_ORDER_DATA.order_data.packages) {
            OMNIVA_M_ORDER_DATA.order_data.packages.forEach((data, index) => {
                if (index > 0) {
                    OMNIVA_M_ORDER_INFO.packagesContainer.insertBefore(OMNIVA_M_ORDER_INFO.generatePacakgeHtml(index + 1), btnContainer);
                }

                if (!data) {
                    return;
                }

                const package = OMNIVA_M_ORDER_INFO.packagesContainer.querySelector(`[data-package="${index + 1}"]`);

                Object.keys(data).forEach(key => {
                    const checkbox = package.querySelector(`input[data-service="${key}"]`);
                    if (!checkbox) {
                        return;
                    }

                    checkbox.checked = true;

                    if (data[key]) {
                        Object.keys(data[key]).forEach(param => {
                            const paramInput = package.querySelector(`input[data-service-value="${key}_${param}"]`);
                            if (paramInput) {
                                paramInput.value = data[key][param];
                            }
                        });
                    }
                });
            });
        }
    },

    addPackage: function() {
        if (!OMNIVA_M_ORDER_INFO.packagesContainer) {
            return;
        }

        const btnContainer = OMNIVA_M_ORDER_INFO.packagesContainer.querySelector('.omniva_m-add-package-btn-container');

        const packages = OMNIVA_M_ORDER_INFO.packagesContainer.querySelectorAll('[data-package]');

        OMNIVA_M_ORDER_INFO.packagesContainer.insertBefore(OMNIVA_M_ORDER_INFO.generatePacakgeHtml(packages.length + 1), btnContainer);
    },

    delPackage: function(index) {
        if (!OMNIVA_M_ORDER_INFO.packagesContainer) {
            return;
        }

        const targetPackage = OMNIVA_M_ORDER_INFO.packagesContainer.querySelector(`[data-package="${index}"]`);

        if (!targetPackage) {
            return;
        }

        targetPackage.remove();
    },

    showLastDelPackageBtn: function() {
        if (!OMNIVA_M_ORDER_INFO.packagesContainer) {
            return;
        }

        const delButtons = OMNIVA_M_ORDER_INFO.packagesContainer.querySelectorAll('.omniva_m-del-package-btn');

        const lastIndex = delButtons.length - 1;
        delButtons.forEach((el, index) => {
            el.style.display = index === lastIndex ? 'inline-block' : 'none';
        });
    },

    generatePacakgeHtml: function(index) {
        const fragment = new DocumentFragment();

        const span = document.createElement("span");
        span.dataset.package = index;

        let multiType = document.querySelector('input[name="omniva_m_multi_type"]')?.value;

        // first package and when set as multiparcel show all available services
        if (index === 1 || !multiType) {
            multiType = 'multiparcel';
        }

        let services = '';
        // no services for international
        if (!OMNIVA_M_ORDER_DATA.is_international) {
            Object.keys(OMNIVA_M_ORDER_DATA.add_services[multiType]).forEach((key) => {
                let input_value = '';
                if (OMNIVA_M_ORDER_DATA.add_services[multiType][key]) {
                    OMNIVA_M_ORDER_DATA.add_services[multiType][key].forEach(value => {
                        input_value += `<label>${value}: <input type="text" data-service-value="${key}_${value}"></input></label>`;
                    });
                }
                services += `
                <div class="col-sm-12 checkbox">
                    <label>
                        <input type="checkbox" data-service="${key}"> ${key}
                    </label>
                    ${input_value}
                </div>
                `
            });
        }

        let del_button = '';
        if (index > 1) {
            del_button = `<button class="btn btn-warning omniva_m-del-package-btn" data-package-del="${index}">${OMNIVA_M_INFO_PANEL_TRANSLATION.del_package_btn}</button>`;
        }

        let packageTitle = OMNIVA_M_INFO_PANEL_TRANSLATION.package_num + index;
        if (!OMNIVA_M_ORDER_DATA.is_international) {
            packageTitle += OMNIVA_M_INFO_PANEL_TRANSLATION.package_num_suffix;
        }

        span.innerHTML = `
            <h4 class="col-sm-12 text-center">
                ${packageTitle}
                ${del_button}
            </h4>
            ${services}
        `;

        fragment.append(span);

        return fragment;
    },
}

document.addEventListener('DOMContentLoaded', function () {
    OMNIVA_M_ORDER_INFO.init();
});