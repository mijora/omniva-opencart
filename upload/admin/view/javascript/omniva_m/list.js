const OMNIVA_M_LIST = {
    confirm_action_no: null, // should have one function at any given time
    confirm_action_yes: null, // should have one function at any given time

    init: function () {
        this.addOverlayElement();
        this.addModalElement();
        this.addLogoToOrderList();
        this.addPrintLabelBtn();
        this.addCallCourierBtn();
        this.addFilterInput();
    },

    showWorking: function (shouldShow, target) {
        const btnEl = document.querySelector(target);

        btnEl.classList[shouldShow ? 'add' : 'remove']('disabled');
        btnEl.querySelector('.fa').classList[shouldShow ? 'add' : 'remove']('hidden');
        btnEl.querySelector('.bs5-spinner-border').classList[shouldShow ? 'remove' : 'add']('hidden');
    },

    addOverlayElement: function () {
        const overlayCss = `
            .btn-omniva_m {
                color: #fff;
                background-color: hsl(24deg 100% 45%);
                border-color: hsl(24deg 100% 40%);
                margin: 0 0.15em;
            }

            .btn-omniva_m:hover,
            .btn-omniva_m:focus {
                color: #fff;
                background-color: hsl(24deg 100% 50%);
            }

            .omniva_m-btn-close span {
                pointer-events: none;
            }

            @keyframes bs5-spinner-border {
                100% { transform: rotate(360deg) }
            }
            
            .bs5-spinner-border {
                display: inline-block;
                width: 1rem;
                height: 1rem;
                border: 0.15em solid currentColor;
                border-right-color: transparent;
                border-radius: 50%;
                -webkit-animation: .75s linear infinite bs5-spinner-border;
                animation: 0.75s linear infinite bs5-spinner-border;
            }
        `;

        const styleEl = document.createElement('style');

        styleEl.innerHTML = overlayCss;

        document.querySelector('head').append(styleEl);
    },

    addFilterInput: function () {
        const urlParams = new URLSearchParams(location.search);

        let filterOnlyOmnivaValue = urlParams.get('filter_omniva_m_only');

        if (filterOnlyOmnivaValue === null) {
            filterOnlyOmnivaValue = 0;
        }
        filterOnlyOmnivaValue = parseInt(filterOnlyOmnivaValue);

        const html = `
            <label class="control-label" for="input-filter_omniva_m_only">${OMNIVA_M_DATA.trans.filter_label_omniva_only}</label>
            <select name="filter_omniva_m_only" id="input-filter_omniva_m_only" class="form-control">
                <option value="0" ${filterOnlyOmnivaValue === 0 ? 'selected' : ''}>${OMNIVA_M_DATA.trans.option_no}</option>
                <option value="1" ${filterOnlyOmnivaValue === 1 ? 'selected' : ''}>${OMNIVA_M_DATA.trans.option_yes}</option>
            </select>
        `;

        let inputWrapper = document.createElement('div');
        inputWrapper.classList.add('form-group');
        inputWrapper.innerHTML = html;

        let filtersBlock = document.querySelector('#filter-order');
        if (!filtersBlock) { // oc2 doesnt have filter element with id
            // falback to finding filter input
            filtersBlock = document.querySelector('input[name="filter_order_id"]').closest('.form-group').parentNode;
        }

        // still nothing? giveup
        if (!filtersBlock) {
            console.error('OMNIVA_M: could not insert filter input');
            return;
        }

        const refElement = filtersBlock.querySelector('.form-group');
        refElement.parentNode.insertBefore(inputWrapper, refElement);
    },

    addLogoToOrderList: function () {
        let logoImg = document.createElement('img');
        logoImg.src = 'view/image/omniva_m/logo.png';
        logoImg.alt = 'Omniva Logo';
        logoImg.classList.add('omniva_m-order-logo');
        document.querySelectorAll(`input[name^='shipping_code'][value^='omniva_m']`)
            .forEach(el => {
                let cols = el.closest('tr').querySelectorAll('td');
                if (cols.length < 2) {
                    return;
                }
                cols[2].append(logoImg.cloneNode());
            })
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
                if (typeof OMNIVA_M_LIST.confirm_action_no === 'function') {
                    OMNIVA_M_LIST.confirm_action_no(e);
                }
                OMNIVA_M_LIST.confirm_action_no = null;
                OMNIVA_M_LIST.confirm_action_yes = null;
                return;
            }

            if (e.target.matches('.omniva_m-btn-yes')) {
                e.preventDefault();
                console.log('confirm: YES');
                if (typeof OMNIVA_M_LIST.confirm_action_yes === 'function') {
                    OMNIVA_M_LIST.confirm_action_yes(e);
                }
                OMNIVA_M_LIST.confirm_action_no = null;
                OMNIVA_M_LIST.confirm_action_yes = null;
                $('.omniva_m-modal').modal('hide');
                return;
            }
        });

        document.body.append(fragment);
    },

    addPrintLabelBtn: function () {
        let btnContainer = document.querySelector('#button-invoice').parentNode;
        let printBtn = document.createElement('a');
        printBtn.href = '#';
        printBtn.id = 'omniva_m_print_labels_btn';
        printBtn.dataset.originalTitle = OMNIVA_M_DATA.trans.tooltip_btn_print_register;
        printBtn.dataset.toggle = 'tooltip';
        printBtn.classList.add('btn', 'btn-omniva_m', 'omniva_m-print-label');
        printBtn.innerHTML = `
            <i class="fa fa-print"></i>
            <div class="bs5-spinner-border hidden"></div>
        `;

        $(printBtn).tooltip();

        printBtn.addEventListener('click', function (e) {
            e.preventDefault();
            OMNIVA_M_LIST.printLabelsAction();
        });

        btnContainer.append(printBtn);
    },

    addPrintManifestBtn: function () {
        let btnContainer = document.querySelector('#button-invoice').parentNode;
        let manifestBtn = document.createElement('a');
        manifestBtn.href = '#';
        manifestBtn.id = 'omniva_m_print_manifest_btn';
        manifestBtn.dataset.originalTitle = OMNIVA_M_DATA.trans.tooltip_btn_manifest;
        manifestBtn.dataset.toggle = 'tooltip';
        manifestBtn.classList.add('btn', 'btn-omniva_m', 'omniva_m-print-manifest');
        manifestBtn.innerHTML = `
            <i class="fa fa-file-pdf-o"></i>
            <div class="bs5-spinner-border hidden"></div>
        `;

        $(manifestBtn).tooltip();

        manifestBtn.addEventListener('click', function (e) {
            e.preventDefault();
            OMNIVA_M_LIST.printManifestAction();
        });

        btnContainer.append(manifestBtn);
    },

    addCallCourierBtn: function () {
        let btnContainer = document.querySelector('#button-invoice').parentNode;
        let callCourierBtn = document.createElement('a');
        callCourierBtn.href = '#';
        callCourierBtn.id = 'omniva_m_print_labels_btn';
        callCourierBtn.dataset.originalTitle = OMNIVA_M_DATA.trans.tooltip_btn_call_courier;
        callCourierBtn.dataset.toggle = 'tooltip';
        callCourierBtn.classList.add('btn', 'btn-omniva_m', 'omniva_m-call-courier');
        callCourierBtn.innerHTML = `
            <i class="fa fa-truck"></i>
            <div class="bs5-spinner-border hidden"></div>
        `;

        $(callCourierBtn).tooltip();

        callCourierBtn.addEventListener('click', function (e) {
            e.preventDefault();
            OMNIVA_M_LIST.callCourierAction();
        });

        btnContainer.append(callCourierBtn);
    },

    callCourierAction: function () {
        OMNIVA_M_LIST.showWorking(true, '.btn-omniva_m.omniva_m-call-courier');

        OMNIVA_M_LIST.confirm_action_yes = function () {
            OMNIVA_M_LIST.showWorking(false, '.btn-omniva_m.omniva_m-call-courier');
            OMNIVA_M_LIST.callCourier();
        };

        OMNIVA_M_LIST.confirm_action_no = function () {
            OMNIVA_M_LIST.showWorking(false, '.btn-omniva_m.omniva_m-call-courier');
        };

        OMNIVA_M_LIST.confirm(OMNIVA_M_DATA.trans.confirm_call_courier + OMNIVA_M_DATA.call_courier_address);
    },

    printLabelsAction: function () {
        const checkedOrdersEl = document.querySelectorAll(`input[name^='selected']:checked`);

        let selectedOrders = [];

        checkedOrdersEl.forEach(el => {
            const shippingCodeEl = el.parentNode.querySelector('input[name^="shipping_code"]');
            if (!shippingCodeEl || !shippingCodeEl.value.startsWith('omniva_m.')) {
                return;
            }

            selectedOrders.push(el.value);
        });

        if (selectedOrders.length < 1) {
            alert(OMNIVA_M_DATA.trans.alert_no_orders);
            return;
        }

        OMNIVA_M_LIST.showWorking(true, '.btn-omniva_m.omniva_m-print-label');

        OMNIVA_M_LIST.confirm_action_yes = function () {
            OMNIVA_M_LIST.showWorking(false, '.btn-omniva_m.omniva_m-print-label');
            OMNIVA_M_LIST.printLabels(selectedOrders);
        };

        OMNIVA_M_LIST.confirm_action_no = function () {
            OMNIVA_M_LIST.showWorking(false, '.btn-omniva_m.omniva_m-print-label');
        };
        OMNIVA_M_LIST.confirm(OMNIVA_M_DATA.trans.confirm_print_labels);
    },

    printLabels: function (orderIds) {
        data = new FormData();
        orderIds.forEach(id => {
            data.append('order_ids[]', id);
        });

        OMNIVA_M_LIST.showWorking(true, '.btn-omniva_m.omniva_m-print-label');
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

                OMNIVA_M_LIST.downloadPdf(json.data.pdf, 'omniva_labels');
            })
            .catch((error) => {
                console.error(error);
                alert(OMNIVA_M_DATA.trans.alert_response_error);
            })
            .finally(() => {
                OMNIVA_M_LIST.showWorking(false, '.btn-omniva_m.omniva_m-print-label');
            });
    },

    callCourier: function () {
        // data = new FormData();

        OMNIVA_M_LIST.showWorking(true, '.btn-omniva_m.omniva_m-call-courier');
        fetch(OMNIVA_M_DATA.ajax_url + '&action=callCourier', {
            method: 'GET',
            // body: data
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
                OMNIVA_M_LIST.showWorking(false, '.btn-omniva_m.omniva_m-call-courier');
            });
    },

    confirm: function (message) {
        const modal = document.querySelector('.omniva_m-modal');
        modal.querySelector('.omniva_m-modal-msg').innerHTML = message;
        $(modal).modal('show');
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
    }
}

document.addEventListener('DOMContentLoaded', function () {
    OMNIVA_M_LIST.init();
});