const OMNIVA_M_SETTINGS = {
    countryList: {},

    showTab: function (tabId) {
        let tabLink = document.querySelector(`a[href="${tabId}"]`);

        if (tabLink) {
            tabLink.click();
        }
    },

    init: function () {
        console.log('Omniva_m settings activated');
        this.initPriceSettings();
        this.globalListener();
        this.listenForContractOrigin();
        this.handleContractCourierOptions();
    },

    addGlobalListener: function (type, selector, callback, options, parent = document) {
        parent.addEventListener(type, (e) => {
            if (e.target.matches(selector)) {
                callback(e);
            }
        }, options);
    },

    globalListener: function() {
        $('[name="country"]').on('select2:select', function (e) {
            const data = e.params.data;
            OMNIVA_M_SETTINGS.showPriceOptions(data.element.value, document.querySelector('#price-table'), true);
        });
    },

    handleContractCourierOptions: function() {
        const originEl = document.querySelector('#input-api-contract-origin');
        const courierOptionsEl = document.querySelector('.omniva_m-courier-options');
        const courierOptionsEnabled = originEl.value == OMNIVA_DATA.contractEnableCourieroptions;

        courierOptionsEl.classList[courierOptionsEnabled ? 'remove' : 'add']('hidden');
    },

    listenForContractOrigin: function() {
        const originEl = document.querySelector('#input-api-contract-origin');

        originEl.addEventListener('change', function(e) {
            OMNIVA_M_SETTINGS.handleContractCourierOptions();
        });
    },

    getCountryPriceLimits: function(country)
    {
        const countryData = OMNIVA_M_SETTINGS.countryList[country];

        return {
            isTerminal: countryData?.omnivaHasTerminals,
            isBaltic: countryData?.omnivaType === 'baltic',
            isInternational: countryData?.omnivaType === 'international'
        };
    },

    showPriceOptions: function(country, mainEl, clearInputs = false) {
        console.log('UPDATE:', country, mainEl);

        if (!mainEl) {
            return;
        }

        const countryData = OMNIVA_M_SETTINGS.getCountryPriceLimits(country);

        mainEl.style.setProperty('--omniva_m-show-terminal', countryData.isTerminal ? 'block' : 'none');
        mainEl.style.setProperty('--omniva_m-show-courier', countryData.isBaltic ? 'block' : 'none');
        mainEl.style.setProperty('--omniva_m-show-services', countryData.isInternational ? 'block' : 'none');
        mainEl.style.setProperty('--omniva_m-show-add-price-btn', (countryData.isBaltic || countryData.isInternational || countryData.isTerminal) ? 'block' : 'none');

        // clear inputs
        if (clearInputs) {
            mainEl.querySelectorAll('[data-omniva-price] input').forEach(item => {
                item.value = '';
            });
        }
    },

    initPriceSettings: function() {
        window.$price_table = $('#price-table');
        this.sortPrices();
        $.ajax({
            type: 'POST',
            url: ajax_url + '&action=getCountries',
            dataType: 'json',
            data: {
                "geo_zone_id": omniva_m_geo_zone_id
            },
            success: function (json) {
                console.log(json);
                OMNIVA_M_SETTINGS.buildHtml(json.data);
            },
            error: function (xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });

        $('#update-terminals-btn').on('click', function (e) {
            e.preventDefault();
            $('#tab-terminals').addClass('omniva_m-overlay');
            $.ajax({
                type: 'GET',
                url: ajax_url + '&action=terminalUpdate',
                dataType: 'json',
                success: function (json) {
                    console.log(json);

                    if (!json.data) {
                        alert('There was an error in terminalUpdate response');
                        return;
                    }

                    if (json.data.error) {
                        alert(json.data.error);
                        return;
                    }

                    $('.omniva_m-terminal-last-update').text(json.data.updated);

                    let html = '';
                    json.data.terminalList.forEach(entry => {
                        html += `
                            <div class="col-sm-4 omniva_m-terminal-info-name">${entry.country}:</div>
                            <div class="col-sm-8 bold">${entry.total}</div>
                        `;
                    });

                    $('.omniva_m-terminal-list').html(html);
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                },
                complete: function (xhr, status) {
                    console.log('Completed with: ', status);
                    $('#tab-terminals').removeClass('omniva_m-overlay');
                }
            });
        });

        // delete price button
        $('#price-table').on('click', '[data-delete-btn]', function (e) {
            e.preventDefault();
            var data = OMNIVA_M_SETTINGS.jsonDecodeFromHTML($(this).parent().parent().get(0).dataset.priceData);
            OMNIVA_M_SETTINGS.deletePrice(data);
        });

        // edit price button
        $('#price-table').on('click', '[data-edit-btn]', function (e) {
            e.preventDefault();
            var data = OMNIVA_M_SETTINGS.jsonDecodeFromHTML($(this).parent().parent().get(0).dataset.priceData);
            OMNIVA_M_SETTINGS.fillPriceModal(data);
            $('.edit-price-modal').show();
        });

        $('#save-price-btn').on('click', function (e) {
            e.preventDefault();
            var data = {
                country: $('.edit-price-modal [name="country"]').val(),
                country_name: $('.edit-price-modal [name="country_name"]').val(),
                terminal_price: $('.edit-price-modal [name="terminal_price"]').val(),
                terminal_price_range_type: $('.edit-price-modal [name="terminal_price_range_type"]').val(),
                courier_price: $('.edit-price-modal [name="courier_price"]').val(),
                courier_price_range_type: $('.edit-price-modal [name="courier_price_range_type"]').val(),
                premium_price: $('.edit-price-modal [name="premium_price"]').val(),
                premium_price_range_type: $('.edit-price-modal [name="premium_price_range_type"]').val(),
                standard_price: $('.edit-price-modal [name="standard_price"]').val(),
                standard_price_range_type: $('.edit-price-modal [name="standard_price_range_type"]').val(),
                economy_price: $('.edit-price-modal [name="economy_price"]').val(),
                economy_price_range_type: $('.edit-price-modal [name="economy_price_range_type"]').val()
            };
            OMNIVA_M_SETTINGS.savePrice(data);
            OMNIVA_M_SETTINGS.cleanPriceModal();
            $('.edit-price-modal').hide();
        });

        $('#cancel-price-btn').on('click', function (e) {
            e.preventDefault();
            OMNIVA_M_SETTINGS.cleanPriceModal();
            $('.edit-price-modal').hide();
        });

        $('#add-price-btn').on('click', function (e) {
            e.preventDefault();
            if ($('#price-table [name="country"]').val() == null) {
                console.log('No country to add');
                return;
            }
            var data = {
                country: $('#price-table [name="country"]').val(),
                country_name: $('#price-table [name="country"] option:selected').text(),
                terminal_price: $('#price-table [name="terminal_price"]').val(),
                terminal_price_range_type: $('#price-table [name="terminal_price_range_type"]').val(),
                courier_price: $('#price-table [name="courier_price"]').val(),
                courier_price_range_type: $('#price-table [name="courier_price_range_type"]').val(),
                economy_price: $('#price-table [name="economy_price"]').val(),
                economy_price_range_type: $('#price-table [name="economy_price_range_type"]').val(),
                standard_price: $('#price-table [name="standard_price"]').val(),
                standard_price_range_type: $('#price-table [name="standard_price_range_type"]').val(),
                premium_price: $('#price-table [name="premium_price"]').val(),
                premium_price_range_type: $('#price-table [name="premium_price_range_type"]').val()
            };
            OMNIVA_M_SETTINGS.savePrice(data);
        });
    },

    buildHtml: function (json) {
        if (json.length < 1) {
            return;
        }

        OMNIVA_M_SETTINGS.countryList = json;

        var html = Object.values(json).map(function (country) {
            if ($('[data-price-row="' + country.iso_code_2 + '"]').length > 0) {
                return '';
            }
            return `<option 
                value="${country.iso_code_2}"
                data-omniva-has-terminals="${country.omnivaHasTerminals}"
                data-omniva-type="${country.omnivaType}"
                >${country.name}</option>`
        }).join('\n');

        $('[name="country"]').html(html);
        $('[name="country"]').trigger('change');
        OMNIVA_M_SETTINGS.showPriceOptions($('[name="country"]').val(), document.querySelector('#price-table'), true);
    },

    updatePriceNotification: function () {
        if ($('#created-prices').find('tr').length > 1) {
            $('#no-price-notification').hide();
            return;
        }

        $('#no-price-notification').show();
    },

    jsonEncodeToHTML: function (json) {
        return btoa(JSON.stringify(json));
    },

    jsonDecodeFromHTML: function (json_string) {
        return JSON.parse(atob(json_string));
    },

    fillPriceModal: function (data) {
        console.log(data);
        $('.edit-price-modal [name="country"]').val(data.country);
        $('.edit-price-modal [name="country_name"]').val(data.country_name);
        $('.edit-price-modal [name="terminal_price"]').val(data.terminal_price);
        $('.edit-price-modal [name="terminal_price_range_type"]').val(data.terminal_price_range_type);
        $('.edit-price-modal [name="courier_price"]').val(data.courier_price);
        $('.edit-price-modal [name="courier_price_range_type"]').val(data.courier_price_range_type);
        $('.edit-price-modal [name="premium_price"]').val(data.premium_price);
        $('.edit-price-modal [name="premium_price_range_type"]').val(data.premium_price_range_type);
        $('.edit-price-modal [name="standard_price"]').val(data.standard_price);
        $('.edit-price-modal [name="standard_price_range_type"]').val(data.standard_price_range_type);
        $('.edit-price-modal [name="economy_price"]').val(data.economy_price);
        $('.edit-price-modal [name="economy_price_range_type"]').val(data.economy_price_range_type);

        // mange visible fields
        OMNIVA_M_SETTINGS.showPriceOptions(data.country, document.querySelector('.edit-price-modal'), false);
    },

    cleanPriceModal: function () {
        $('.edit-price-modal [name="country"]').val('');
        $('.edit-price-modal [name="country_name"]').val('');
        $('.edit-price-modal [name="terminal_price"]').val('');
        $('.edit-price-modal [name="terminal_price_range_type"]').val('');
        $('.edit-price-modal [name="courier_price"]').val('');
        $('.edit-price-modal [name="courier_price_range_type"]').val('');
        $('.edit-price-modal [name="premium_price"]').val('');
        $('.edit-price-modal [name="premium_price_range_type"]').val('');
        $('.edit-price-modal [name="standard_price"]').val('');
        $('.edit-price-modal [name="standard_price_range_type"]').val('');
        $('.edit-price-modal [name="economy_price"]').val('');
        $('.edit-price-modal [name="economy_price_range_type"]').val('');
    },

    sortPrices: function () {
        var pricesHTML = $('#created-prices').find('[data-price-row]').remove();
        pricesHTML.sort(function (a, b) {
            return $(a).find('td').first().text().localeCompare($(b).find('td').first().text());
        });
        $('#created-prices').append(pricesHTML);
    },

    deletePrice: function (data) {
        console.log('Deletion data:', data);
        $('#price-table').addClass('omniva_m-overlay');
        $.ajax({
            type: 'POST',
            url: ajax_url + '&action=deletePrice',
            dataType: 'json',
            data: data,
            success: function (json) {
                console.log(json);
                $('[name="country"]').append($('<option value="' + data.country + '">' + data.country_name + '</option>'));
                $('[name="country"]').trigger('change');
                OMNIVA_M_SETTINGS.showPriceOptions($('[name="country"]').val(), document.querySelector('#price-table'), true);
                $('[data-price-row="' + data.country + '"]').remove();
                OMNIVA_M_SETTINGS.updatePriceNotification();
            },
            error: function (xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            },
            complete: function (xhr, status) {
                console.log('Completed with: ', status);
                $('#price-table').removeClass('omniva_m-overlay');
            }
        });
    },

    savePrice: function (data) {
        $('#price-table').addClass('omniva_m-overlay');
        $.ajax({
            type: 'POST',
            url: ajax_url + '&action=savePrice',
            dataType: 'json',
            data: data,
            success: function (json) {
                console.log(json);
                const countryData = OMNIVA_M_SETTINGS.getCountryPriceLimits(data.country);

                $('[name="country"]').find('[value="' + data.country + '"]').remove();
                var buttons = `
                    <button data-edit-btn class="btn btn-primary"><i class="fa fa-edit"></i></button>
                    <button data-delete-btn class="btn btn-danger"><i class="fa fa-trash"></i></button>
                `;

                let priceHtml = '';

                if (countryData.isTerminal) {
                    priceHtml += `
                        <tr>
                            <td>${OMNIVA_DATA.trans.omniva_m_label_price_terminal}</td>
                            <td>${json.data.terminal_price}</td>
                            <td>${price_range_types[data.terminal_price_range_type]}</td>
                        </tr>
                    `;
                }

                if (countryData.isInternational) {
                    priceHtml += `
                        <tr>
                            <td>${OMNIVA_DATA.trans.omniva_m_label_price_premium}</td>
                            <td>${json.data.premium_price}</td>
                            <td>${price_range_types[data.premium_price_range_type]}</td>
                        </tr>
                        <tr>
                            <td>${OMNIVA_DATA.trans.omniva_m_label_price_standard}</td>
                            <td>${json.data.standard_price}</td>
                            <td>${price_range_types[data.standard_price_range_type]}</td>
                        </tr>
                        <tr>
                            <td>${OMNIVA_DATA.trans.omniva_m_label_price_economy}</td>
                            <td>${json.data.economy_price}</td>
                            <td>${price_range_types[data.economy_price_range_type]}</td>
                        </tr>
                    `;
                } else {
                    priceHtml += `
                        <td>${OMNIVA_DATA.trans.omniva_m_label_price_courier}</td>
                        <td>${json.data.courier_price}</td>
                        <td>${price_range_types[data.courier_price_range_type]}</td>
                    `;
                }

                $row = $(`
                    <tr data-price-row="${data.country}" data-price-data='${OMNIVA_M_SETTINGS.jsonEncodeToHTML(json.data)}'>
                        <td>${data.country_name}</td>
                        <td>
                            <table class="table table-striped table-hover">
                            ${priceHtml}
                            </table>
                        </td>
                        <td>${buttons}</td>
                    </tr>
                `);

                if ($('#created-prices [data-price-row="' + data.country + '"]').length > 0) {
                    // Editing price, so remove old entry
                    $('#created-prices [data-price-row="' + data.country + '"]').remove();
                }
                // Add price data to table
                $('#created-prices').append($row);
                OMNIVA_M_SETTINGS.updatePriceNotification();
                OMNIVA_M_SETTINGS.sortPrices();

                // update price fields
                OMNIVA_M_SETTINGS.showPriceOptions($('[name="country"]').val(), document.querySelector('#price-table'), true);
            },
            error: function (xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            },
            complete: function (xhr, status) {
                console.log('Completed with: ', status);
                $('#price-table').removeClass('omniva_m-overlay');
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', function () {
    OMNIVA_M_SETTINGS.showTab(omniva_m_current_tab);
    OMNIVA_M_SETTINGS.init();
    $('#content').removeClass('omniva_m-overlay');
});