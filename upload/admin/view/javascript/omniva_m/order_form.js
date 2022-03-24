const OMNIVA_M_ORDER_FORM = {
    init: function () {
        //
    },

    addOrderInformationPanel: function(){
        const historyPanel = document.querySelector('#history').closest('.panel');

        const omnivaPanel = document.querySelector('#omniva_m-panel');

        historyPanel.parentNode.insertBefore(historyPanel, omnivaPanel);
    },
}

document.addEventListener('DOMContentLoaded', function () {
    OMNIVA_M_ORDER_FORM.init();
});