const OMNIVA_M_MANIFEST = {
    current_page: 1,
    confirm_action_no: null, // should have one function at any given time
    confirm_action_yes: null, // should have one function at any given time
    reload_after_print: false, // used by printLabels function to determine if need to reload orders page

    init: function () {
        this.addModalElement();
        this.listenForCheckAll();
        this.listenForActions();
        this.listenForFilters();
        this.listenForPaginator();
        this.loadOrders(1);
    },

    showWorking: function (shouldShow, target) {
        let btnEl = target;
        if (!(target instanceof Node)) {
            btnEl = document.querySelector(target);
        }

        btnEl.classList[shouldShow ? 'add' : 'remove']('disabled');
        btnEl.querySelector('.fa').classList[shouldShow ? 'add' : 'remove']('hidden');
        btnEl.querySelector('.bs5-spinner-border').classList[shouldShow ? 'remove' : 'add']('hidden');
    },

    listenForCheckAll: function () {
        document.querySelector('#check-all-input').addEventListener('change', function (e) {
            const status = this.checked;
            const tableEl = document.querySelector('#omniva_m-manifest-orders');
            const checkboxes = tableEl.querySelectorAll('input[name*=selected]');
            checkboxes.forEach(item => {
                item.checked = status;
            });
        });
    },

    listenForFilters: function () {
        document.querySelector('#filter-order').addEventListener('click', function (e) {
            if (e.target.matches('#button-filter')) {
                e.preventDefault();
                console.log('Filtering...');
                OMNIVA_M_MANIFEST.loadOrders();
                return;
            }
        });
    },

    listenForActions: function () {
        document.querySelector('#header-action-buttons').addEventListener('click', function (e) {
            OMNIVA_M_MANIFEST.handleActionButtons(e);
        });
        document.querySelector('#omniva_m-manifest-orders').addEventListener('click', function (e) {
            OMNIVA_M_MANIFEST.handleActionButtons(e);
        });
    },

    handleActionButtons: function (e) {
        if (e.target.matches('.omniva_m-btn-order-action')) {
            e.preventDefault();
            let action = e.target.dataset.action;

            // button for single order
            if (action === 'printLabel') {
                console.log('Single print...');
                const checkbox = document.querySelector('input[name*="selected"][value="' + e.target.dataset.orderId + '"]');
                if (checkbox && parseInt(checkbox.dataset.hasBarcode) === 0) {
                    OMNIVA_M_MANIFEST.reload_after_print = true; // mark as needed to reload
                }
                OMNIVA_M_MANIFEST.printLabels([e.target.dataset.orderId], e.target);
                return;
            }

            // button for multiple selections
            if (action === 'printLabels') {
                console.log('Mass print...');
                OMNIVA_M_MANIFEST.printLabelsAction(e.target);
                return;
            }

            // button for single generated manifest
            if (action === 'printManifest') {
                console.log('Manifest print...');
                OMNIVA_M_MANIFEST.printManifest(e.target.dataset.manifestId, e.target);
                return;
            }

            // button to create manifest
            if (action === 'createManifest') {
                console.log('Manifest create...');
                OMNIVA_M_MANIFEST.createManifestAction(e.target);
                return;
            }

            // button to call courier
            if (action === 'callCourier') {
                console.log('Calling courier...');
                OMNIVA_M_MANIFEST.callCourierAction(e.target);
                return;
            }

            return;
        }
    },

    listenForPaginator: function () {
        document.querySelector('#omniva_m_pagination').addEventListener('click', function (e) {
            if (e.target.matches('.omniva_m-btn-previous')) {
                e.preventDefault();
                console.log('Previous page', OMNIVA_M_MANIFEST.current_page - 1);
                OMNIVA_M_MANIFEST.loadOrders(OMNIVA_M_MANIFEST.current_page - 1);
                return;
            }

            if (e.target.matches('.omniva_m-btn-next')) {
                e.preventDefault();
                console.log('Next page', OMNIVA_M_MANIFEST.current_page + 1);
                OMNIVA_M_MANIFEST.loadOrders(OMNIVA_M_MANIFEST.current_page + 1);
                return;
            }
        });
    },

    showOverlay: function (shouldShow, target) {
        if (!target) {
            target = '#content';
        }

        const overlayEl = document.querySelector(target);

        if (!overlayEl) {
            return;
        }

        overlayEl.classList[shouldShow ? 'add' : 'remove']('omniva_m-overlay');
    },

    getFilters: function () {
        let filters = {};

        const filtersEl = document.querySelector('#filter-order');

        const filterOrderId = filtersEl.querySelector('[name="filter_order_id"]').value;
        const filterCustomer = filtersEl.querySelector('[name="filter_customer"]').value;
        const filterBarcode = filtersEl.querySelector('[name="filter_barcode"]').value;
        const filterHasBarcode = filtersEl.querySelector('[name="filter_has_barcode"]').value;
        const filterHasManifest = filtersEl.querySelector('[name="filter_has_manifest"]').value;
        const filterOrderStatusId = filtersEl.querySelector('[name="filter_order_status_id"]').value;

        if (filterOrderId !== '' && parseInt(filterOrderId) > 0) {
            filters.filter_order_id = filterOrderId;
        }
        if (filterCustomer !== '') {
            filters.filter_customer = filterCustomer;
        }
        if (filterBarcode !== '') {
            filters.filter_barcode = filterBarcode;
        }
        if (filterHasBarcode !== '0') {
            filters.filter_has_barcode = filterHasBarcode;
        }
        if (filterHasManifest !== '0') {
            filters.filter_has_manifest = filterHasManifest;
        }
        if (filterOrderStatusId !== '0') {
            filters.filter_order_status_id = filterOrderStatusId;
        }

        return filters;
    },

    loadOrders: function (page) {
        if (!page) {
            page = 1;
        }

        data = new FormData();
        data.append('page', page);

        const filters = OMNIVA_M_MANIFEST.getFilters();
        const filterKeys = Object.keys(filters);

        if (filterKeys.length > 0) {
            filterKeys.forEach(filterKey => {
                data.append(filterKey, filters[filterKey]);
            });
        }

        OMNIVA_M_MANIFEST.showOverlay(true);
        fetch(OMNIVA_M_DATA.ajax_url + '&action=getManifestOrders', {
            method: 'POST',
            body: data
        })
            .then(res => res.json())
            .then(json => {
                console.log(json);

                if (!json.data && json.data !== false) {
                    alert(OMNIVA_M_DATA.trans.bad_response);
                    return;
                }

                if (typeof json.data.error !== 'undefined') {
                    alert(json.data.error);
                    return;
                }

                OMNIVA_M_MANIFEST.renderOrderList(json.data);
            })
            .finally(() => {
                OMNIVA_M_MANIFEST.showOverlay(false);
            });
    },

    renderPagination: function (page, total_pages) {
        page = parseInt(page);
        total_pages = parseInt(total_pages);
        const paginationEl = document.querySelector('#omniva_m_pagination');

        OMNIVA_M_MANIFEST.current_page = page > 0 ? page : 1;



        if (total_pages <= 1) {
            paginationEl.classList.add('hidden');
            return;
        }

        paginationEl.querySelector('.omniva_m-current-page').textContent = page;
        paginationEl.querySelector('.omniva_m-total-pages').textContent = total_pages;

        paginationEl.querySelector('.omniva_m-btn-previous').classList[page === 1 ? 'add' : 'remove']('hidden');

        paginationEl.querySelector('.omniva_m-btn-next').classList[page === total_pages ? 'add' : 'remove']('hidden');

        paginationEl.classList.remove('hidden');
    },

    renderOrderList: function (data) {
        const orderListEl = document.querySelector('#omniva_m-manifest-orders');

        let html = '';

        if (data.orders.length < 1) {
            orderListEl.innerHTML = OMNIVA_M_MANIFEST.getNoResultHtml();
            OMNIVA_M_MANIFEST.renderPagination(1, 1);
            return;
        }

        data.orders.forEach(order => {
            let barcodes = order.barcodes;
            let hasBarcodes = 0;
            if (order.is_error === null || parseInt(order.is_error) === 0) {
                barcodes = barcodes === null ? '' : JSON.parse(barcodes).join(', ');
                if (barcodes.length > 0) {
                    hasBarcodes = 1;
                }
            }

            let actions = `
                <a href="#" class="btn btn-omniva_m omniva_m-btn-order-action"
                    data-order-id="${order.order_id}" data-action="printLabel"
                    data-original-title="${OMNIVA_M_DATA.trans.tooltip_btn_print_register}" data-toggle="tooltip"
                >
                    <i class="fa fa-print"></i>
                    <div class="bs5-spinner-border hidden"></div>
                </a>
            `;

            if (order.manifest_id > 0) {
                actions += `
                    <a href="#" class="btn btn-omniva_m omniva_m-btn-order-action"
                        data-manifest-id="${order.manifest_id}" data-action="printManifest"
                        data-original-title="${OMNIVA_M_DATA.trans.tooltip_btn_manifest}" data-toggle="tooltip"
                    >
                        <i class="fa fa-file-pdf-o"></i>
                        <div class="bs5-spinner-border hidden"></div>
                    </a>
                `;
            }

            html += `
                <tr>
                    <td class="text-center">
                        <input type="checkbox" name="selected[]" value="${order.order_id}" 
                            data-has-barcode="${hasBarcodes}"
                            data-has-manifest="${order.manifest_id > 0 ? 1 : 0}"
                        />
                        <input type="hidden" name="shipping_code[]" value="${order.shipping_code}" />
                    </td>
                    <td class="text-right">${order.order_id}</td>
                    <td class="text-left"><a target="_blank" href="${OMNIVA_M_DATA.order_url}&order_id=${order.order_id}">${order.customer}</a></td>
                    <td class="text-left">${order.order_status}</td>
                    <td class="text-right">${barcodes}</td>
                    <td class="text-left">${order.manifest_id > 0 ? order.manifest_id : ''}</td>
                    <td class="text-right">
                        <div style="min-width: 120px;">
                            <div class="btn-group">
                                ${actions}
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        });

        orderListEl.innerHTML = html;

        OMNIVA_M_MANIFEST.renderPagination(data.current_page, data.total_pages);
        $('#omniva_m-manifest-orders .omniva_m-btn-order-action').tooltip();
    },

    getNoResultHtml: function () {
        return `
            <tr>
                <td class="text-center" colspan="7">${OMNIVA_M_DATA.trans.no_results}</td>
            </tr>
        `;
    },

    printLabelsAction: function (loadingTarget) {
        const checkedOrdersEl = document.querySelectorAll(`input[name^='selected']:checked`);

        let selectedOrders = [];
        let hasOrdersWithoutBarcodes = false;

        checkedOrdersEl.forEach(el => {
            selectedOrders.push(el.value);
            if (parseInt(el.dataset.hasBarcode) === 0) {
                hasOrdersWithoutBarcodes = true;
            }
        });

        if (selectedOrders.length < 1) {
            alert(OMNIVA_M_DATA.trans.alert_no_orders);
            return;
        }

        // if all orders marked with barcodes skip confirmation
        if (!hasOrdersWithoutBarcodes) {
            OMNIVA_M_MANIFEST.printLabels(selectedOrders, loadingTarget);
            return;
        }

        OMNIVA_M_MANIFEST.showWorking(true, loadingTarget);

        OMNIVA_M_MANIFEST.confirm_action_yes = function () {
            OMNIVA_M_MANIFEST.showWorking(false, loadingTarget);
            OMNIVA_M_MANIFEST.reload_after_print = true; // mark as needed to reload
            OMNIVA_M_MANIFEST.printLabels(selectedOrders, loadingTarget);
        };

        OMNIVA_M_MANIFEST.confirm_action_no = function () {
            OMNIVA_M_MANIFEST.showWorking(false, loadingTarget);
        };
        OMNIVA_M_MANIFEST.confirm(OMNIVA_M_DATA.trans.confirm_print_labels);
    },

    printLabels: function (orderIds, loadingTarget) {
        data = new FormData();
        orderIds.forEach(id => {
            data.append('order_ids[]', id);
        });

        if (loadingTarget) {
            OMNIVA_M_MANIFEST.showWorking(true, loadingTarget);
        }
        fetch(OMNIVA_M_DATA.ajax_url + '&action=printLabel', {
            method: 'POST',
            body: data
        })
            .then(res => res.json())
            .then(json => {
                console.log(json);

                if (!json.data && json.data !== false) {
                    return;
                }

                if (typeof json.data.error !== 'undefined') {
                    alert(OMNIVA_M_DATA.trans.alert_response_error + json.data.error);
                    return;
                }

                if (typeof json.data.pdf === 'undefined') {
                    alert(OMNIVA_M_DATA.trans.alert_no_pdf);
                    return;
                }

                OMNIVA_M_MANIFEST.downloadPdf(json.data.pdf, 'omniva_labels');
            })
            .catch((error) => {
                console.error(error);
                alert(OMNIVA_M_DATA.trans.alert_response_error);
            })
            .finally(() => {
                if (loadingTarget) {
                    OMNIVA_M_MANIFEST.showWorking(false, loadingTarget);
                }

                // check if need to reload, set need to reload to false
                if (OMNIVA_M_MANIFEST.reload_after_print) {
                    OMNIVA_M_MANIFEST.reload_after_print = false;
                    OMNIVA_M_MANIFEST.loadOrders(OMNIVA_M_MANIFEST.current_page);
                }
            });
    },

    printManifest: function (manifestId, loadingTarget) {
        data = new FormData();
        data.append('manifest_id', manifestId);

        if (loadingTarget) {
            OMNIVA_M_MANIFEST.showWorking(true, loadingTarget);
        }
        fetch(OMNIVA_M_DATA.ajax_url + '&action=printManifest', {
            method: 'POST',
            body: data
        })
            .then(res => res.json())
            .then(json => {
                console.log(json);

                if (!json.data && json.data !== false) {
                    return;
                }

                if (typeof json.data.error !== 'undefined') {
                    alert(OMNIVA_M_DATA.trans.alert_response_error + json.data.error);
                    return;
                }

                if (typeof json.data.pdf === 'undefined') {
                    alert(OMNIVA_M_DATA.trans.alert_no_pdf);
                    return;
                }

                OMNIVA_M_MANIFEST.downloadPdf(json.data.pdf, 'omniva_manifest');
            })
            .catch((error) => {
                console.error(error);
                alert(OMNIVA_M_DATA.trans.alert_response_error);
            })
            .finally(() => {
                if (loadingTarget) {
                    OMNIVA_M_MANIFEST.showWorking(false, loadingTarget);
                }
            });
    },

    createManifestAction: function (loadingTarget) {
        const checkedOrdersEl = document.querySelectorAll(`input[name^='selected']:checked`);

        let selectedOrders = [];

        checkedOrdersEl.forEach(el => {
            // skip if order has manifest or has no barcodes
            if (parseInt(el.dataset.hasManifest) !== 0 || parseInt(el.dataset.hasBarcode) === 0) {
                return;
            }

            selectedOrders.push(el.value);
        });

        if (selectedOrders.length < 1) {
            alert(OMNIVA_M_DATA.trans.alert_no_orders);
            return;
        }

        console.log('Printing manifest for orders:', selectedOrders);

        OMNIVA_M_MANIFEST.showWorking(true, loadingTarget);

        OMNIVA_M_MANIFEST.confirm_action_yes = function () {
            OMNIVA_M_MANIFEST.showWorking(false, loadingTarget);
            OMNIVA_M_MANIFEST.createManifest(selectedOrders, loadingTarget);
        };

        OMNIVA_M_MANIFEST.confirm_action_no = function () {
            OMNIVA_M_MANIFEST.showWorking(false, loadingTarget);
        };

        OMNIVA_M_MANIFEST.confirm(OMNIVA_M_DATA.trans.confirm_create_manifest + ' ' + selectedOrders.join(', '))
    },

    createManifest: function (orderIds, loadingTarget) {
        data = new FormData();
        orderIds.forEach(id => {
            data.append('order_ids[]', id);
        });

        if (loadingTarget) {
            OMNIVA_M_MANIFEST.showWorking(true, loadingTarget);
        }
        fetch(OMNIVA_M_DATA.ajax_url + '&action=createManifest', {
            method: 'POST',
            body: data
        })
            .then(res => res.json())
            .then(json => {
                console.log(json);

                if (!json.data && json.data !== false) {
                    return;
                }

                if (typeof json.data.error !== 'undefined') {
                    alert(OMNIVA_M_DATA.trans.alert_response_error + json.data.error);
                    return;
                }

                if (typeof json.data.pdf === 'undefined') {
                    alert(OMNIVA_M_DATA.trans.alert_no_pdf);
                    return;
                }

                OMNIVA_M_MANIFEST.downloadPdf(json.data.pdf, 'omniva_manifest');
            })
            .catch((error) => {
                console.error(error);
                alert(OMNIVA_M_DATA.trans.alert_response_error);
            })
            .finally(() => {
                if (loadingTarget) {
                    OMNIVA_M_MANIFEST.showWorking(false, loadingTarget);
                }

                OMNIVA_M_MANIFEST.reload_after_print = false;
                OMNIVA_M_MANIFEST.loadOrders(OMNIVA_M_MANIFEST.current_page);
            });
    },

    downloadPdf: function (data, filename) {
        const pdfContent = `data:application/pdf;base64,${data}`;

        var encodedUri = encodeURI(pdfContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", filename + ".pdf");
        document.body.appendChild(link); // Required for FF

        link.click(); // This will download the data file

        link.remove();
    },

    addModalElement: function () {
        const html = `
        <div class="modal omniva_m-modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close omniva_m-btn-close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Omniva</h4>
                    </div>
                    
                    <div class="modal-body">
                        <p class="omniva_m-modal-msg"></p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger omniva_m-btn-no" data-dismiss="modal">${OMNIVA_M_DATA.trans.btn_no}</button>
                        <button type="button" class="btn btn-success omniva_m-btn-yes">${OMNIVA_M_DATA.trans.btn_yes}</button>
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->
        `;

        const fragment = document.createElement('div');
        fragment.innerHTML = html;

        fragment.addEventListener('click', function (e) {
            if (e.target.matches('.omniva_m-btn-close') || e.target.matches('.omniva_m-btn-no')) {
                e.preventDefault();
                console.log('confirm: NO');
                if (typeof OMNIVA_M_MANIFEST.confirm_action_no === 'function') {
                    OMNIVA_M_MANIFEST.confirm_action_no(e);
                }
                OMNIVA_M_MANIFEST.confirm_action_no = null;
                OMNIVA_M_MANIFEST.confirm_action_yes = null;
                return;
            }

            if (e.target.matches('.omniva_m-btn-yes')) {
                e.preventDefault();
                console.log('confirm: YES');
                if (typeof OMNIVA_M_MANIFEST.confirm_action_yes === 'function') {
                    OMNIVA_M_MANIFEST.confirm_action_yes(e);
                }
                OMNIVA_M_MANIFEST.confirm_action_no = null;
                OMNIVA_M_MANIFEST.confirm_action_yes = null;
                $('.omniva_m-modal').modal('hide');
                return;
            }
        });

        document.body.append(fragment);
    },

    callCourierAction: function (loadingTarget) {
        OMNIVA_M_MANIFEST.showWorking(true, loadingTarget);

        OMNIVA_M_MANIFEST.confirm_action_yes = function () {
            OMNIVA_M_MANIFEST.showWorking(false, loadingTarget);
            OMNIVA_M_MANIFEST.callCourier(loadingTarget);
        };

        OMNIVA_M_MANIFEST.confirm_action_no = function () {
            OMNIVA_M_MANIFEST.showWorking(false, loadingTarget);
        };

        OMNIVA_M_MANIFEST.confirm(OMNIVA_M_DATA.trans.confirm_call_courier + OMNIVA_M_DATA.call_courier_address);
    },

    callCourier: function (loadingTarget) {
        if (loadingTarget) {
            OMNIVA_M_MANIFEST.showWorking(true, loadingTarget);
        }
        fetch(OMNIVA_M_DATA.ajax_url + '&action=callCourier', {
            method: 'GET',
        })
            .then(res => res.json())
            .then(json => {
                console.log(json);

                if (typeof json.data === 'undefined') {
                    alert(OMNIVA_M_DATA.trans.alert_bad_response);
                    return;
                }

                if (typeof json.data.error !== 'undefined') {
                    alert(OMNIVA_M_DATA.trans.alert_response_error + json.data.error);
                    return;
                }

                if (json.data) {
                    alert(OMNIVA_M_DATA.trans.notify_courrier_called);
                    return;
                }

                alert(OMNIVA_M_DATA.trans.notify_courrier_call_failed);
            })
            .catch((error) => {
                console.error(error);
                alert(OMNIVA_M_DATA.trans.alert_response_error);
            })
            .finally(() => {
                if (loadingTarget) {
                    OMNIVA_M_MANIFEST.showWorking(false, loadingTarget);
                }
            });
    },

    confirm: function (message) {
        const modal = document.querySelector('.omniva_m-modal');
        modal.querySelector('.omniva_m-modal-msg').innerHTML = message;
        $(modal).modal('show');
    },
};

document.addEventListener('DOMContentLoaded', function () {
    OMNIVA_M_MANIFEST.init();
    OMNIVA_M_MANIFEST.showOverlay(false);
});