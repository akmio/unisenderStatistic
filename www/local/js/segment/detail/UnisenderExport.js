var UnisenderExport = BX.namespace('UnisenderExport');

UnisenderExport.createSaveButton = function(segmentIdHlb = '') {
    var panelWidget = BX.findChildByClassName(document, 'webform-buttons', true);
    var panelCategoryWidget = BX.findChildByClassName(panelWidget, 'sender-footer-container', true);
    var buttonCancel = document.getElementById("sender-ui-button-panel-cancel");

    var url = document.location.pathname.replace(/\/$/, '');
    var segmentId = url.replace('/marketing/segment/edit/', '');

    var disabledBt = '';
    if (segmentId == segmentIdHlb){
        disabledBt = 'disabled'
    }
    var saveAndExportButton = BX.create(
        'a',
        {
            type: 'file',
            attrs: {
                id: 'send-unisender',
                className: 'ui-btn ui-btn-success',
                href: 'javascript:void(0);',
                disabled: disabledBt,
            },
            text: 'Сохранить и отправить в Unisender'
        }
    );

    BX.bind(saveAndExportButton, 'click', UnisenderExport.Export);
    panelCategoryWidget.insertBefore(saveAndExportButton, buttonCancel);
};

UnisenderExport.Export = function () {
    document.querySelector("a#send-unisender").setAttribute('disabled', true);

    var url = document.location.pathname.replace(/\/$/, '');
    var segmentId = url.replace('/marketing/segment/edit/', '');
    var segmentName = document.querySelector("input[name='NAME']").value;
    var checkboxAuto = document.querySelector("input[name='isAuto']");
    var segmentCheckAuto = '';

    if (checkboxAuto.checked) {
        segmentCheckAuto = 1;
    } else {
        segmentCheckAuto = 0;

    }

    BX.ajax({

        url: "/local/ajax/segment/sendToUnisender.php",
        data: {
            segmentId: segmentId,
            segmentName: segmentName,
            isAuto: segmentCheckAuto
        },
        method: 'POST',
        dataType: 'json',
        timeout: 86400,
        async: false,

        onsuccess: function (data) {

        },
        onfailure: function (error) {

        }

    });

    $('[name="post_form"]').submit();
};

UnisenderExport.createCheckbox = function(checkboxAutoHlb = '') {

    var panelWidget = BX.findChildByClassName(document, 'webform-buttons', true);
    var panelCategoryWidget = BX.findChildByClassName(panelWidget, 'sender-footer-container', true);

    var checkboxStatus = '';
    if (checkboxAutoHlb == 1) {
        checkboxStatus = 'checked';
    }

    var complainCheckbox = BX.create(
        'div',
        {
            attrs: {
                className: 'sender-add-template-container',
            },
            children: [
                BX.create(
                    'label',
                    {
                        attrs: {
                            className: 'sender-add-template-label',
                            id: 'auto-label',
                        },
                        children: [
                            BX.create(
                                'input',
                                {
                                    attrs: {
                                        className: 'sender-add-template-checkbox',
                                        type: 'checkbox',
                                        name: 'isAuto',
                                        value: 'Y',
                                        checked: checkboxStatus
                                    },
                                }
                            ),
                        ],
                    }
                ),
            ]
        }
    );

    panelCategoryWidget.appendChild(complainCheckbox);
    document.getElementById('auto-label').append(' Автоматически передавать новые контакты');
};

UnisenderExport.submitCheckbox = function() {
    var formSelec = document.querySelector('form[name=post_form]');

    formSelec.addEventListener('submit', function() {

        var url = document.location.pathname.replace(/\/$/, '');
        var segmentId = url.replace('/marketing/segment/edit/', '');
        var checkboxAuto = document.querySelector("input[name='isAuto']");
        var segmentCheckAuto = '';

        if (checkboxAuto.checked) {
            segmentCheckAuto = 1;
        } else {
            segmentCheckAuto = 0;

        }

        BX.ajax({

            url: "/local/ajax/segment/sendToUnisender.php",
            data: {
                segmentId: segmentId,
                isAuto: segmentCheckAuto,
                clickSaveBtn: 'Y'
            },
            method: 'POST',
            dataType: 'json',
            timeout: 86400,
            async: false,

            onsuccess: function (data) {

            },
            onfailure: function (error) {

            }

        });
    });
};

