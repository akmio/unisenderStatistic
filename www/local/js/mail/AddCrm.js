var AddButtonCrm = BX.namespace('AddButtonCrm');
AddButtonCrm.init = function(){
    this.createAddCrmButton();

    BX.addCustomEvent( "BX.Main.grid:paramsUpdated", BX.delegate(function(data){
        AddButtonCrm.createAddCrmButton();
        })
    );

};
AddButtonCrm.createAddCrmButton = function() {

    var panelWidget = BX.findChildByClassName(document, 'main-grid-table');
    var panelCategoryWidget = BX.findChild(panelWidget, { tag: 'tbody'});
    var arrMail = BX.findChild(panelCategoryWidget, { tag: 'tr', class: 'main-grid-row main-grid-row-body'}, true, true);

    if (arrMail != null){
        arrMail.shift();
    }

    if (arrMail != null) {

        document.addEventListener('DOMContentLoaded', function() {
            // Выбираем все кнопки с классом 'mail-binding-crm'
            let crmButtons = document.querySelectorAll('.mail-binding-crm');
            AddButtonCrm.deleteOldCrmButton(crmButtons);
        });

        // Выбираем все кнопки с классом 'mail-binding-crm'
        let crmButtons = document.querySelectorAll('.mail-binding-crm');
        if (crmButtons.length > 0){
            AddButtonCrm.deleteOldCrmButton(crmButtons);
        }

        arrMail.forEach(function (elem) {

            let gridСell = elem.querySelectorAll('.main-grid-cell-left');

            //Проверка на наличие кнопки "в CRM"
            for (let index = 0; index < gridСell.length; index++) {
                if (gridСell[index].querySelectorAll('.mail-binding-crm-custom').length !== 0){
                    return;
                }
            }

            //получаем email
            let messageNameBlock = gridСell[0].querySelector('.mail-msg-from-title');
            let messageSpan = gridСell[0].querySelector('span.mail-name-block');
            if (!!messageSpan){
                let idMessage = messageSpan.getAttribute('data-message-id');
                let nameTitle = messageNameBlock.title;
                let splitNameTitle = nameTitle.split(' / ');
                let name = splitNameTitle[0];
                let email = '';
                if (splitNameTitle[1] === undefined){
                   email = splitNameTitle[0];
                }else {
                    email = splitNameTitle[1];
                }

                let creatNewCrmButton = BX.create(
                    'td',
                    {
                        type: 'file',
                        attrs: {
                            className: 'main-grid-cell main-grid-cell-left',
                            style: '',
                        },
                        dataset: {
                            editable: 'true',
                        },
                        children: [
                            BX.create(
                                'div',
                                {
                                    attrs: {
                                        className: 'main-grid-cell-inner',
                                    },
                                    children: [
                                        BX.create(
                                            'span',
                                            {
                                                attrs: {
                                                    className: 'main-grid-cell-content',
                                                },
                                                dataset: {
                                                    //prevent-default: 'true',
                                                },
                                                children: [
                                                    BX.create(
                                                        'a',
                                                        {
                                                            attrs: {
                                                                className: 'mail-ui-binding ui-btn-light-border ui-btn ui-btn-xs ui-btn-round ui-btn-no-caps mail-binding-crm-custom mail-ui-not-active js-bind-'+idMessage,
                                                                id: 'mess'+idMessage,
                                                                title: 'В CRM',
                                                            },
                                                            dataset:{
                                                                email: email,
                                                            },
                                                            text: 'В CRM',
                                                        }
                                                    ),
                                                ],
                                            }
                                        ),
                                    ],
                                }
                            ),
                        ],
                    }
                );

                elem.insertBefore(creatNewCrmButton,gridСell[4]);
                let buttonNewCrm = creatNewCrmButton.querySelector('a#mess'+idMessage);
                buttonNewCrm.onclick = function(){
                    let email = buttonNewCrm.getAttribute('data-email');
                    let idContact;

                    BX.ajax({

                        url: "/local/ajax/mail/getContact.php",
                        data: {
                            email: email,
                        },
                        method: 'POST',
                        dataType: 'json',
                        timeout: 86400,
                        async: false,
                        onsuccess: function (data) {
                            idContact = data;
                        },
                        onfailure: function (error) {
                        }

                    });

                    if (idContact){
                        const url = "/crm/lead/details/0/?contact_id=" + idContact+"&SOURCE_ID=1";
                        BX.SidePanel.Instance.open(url);
                    }else{
                        const url = "/crm/lead/details/0/?NAME="+name+"&SOURCE_ID=1";
                        BX.SidePanel.Instance.open(url);
                        for (let index = 0; index < BX.SidePanel.Instance.openSliders.length; index++) {
                            const slider = BX.SidePanel.Instance.openSliders[index];
                            if(url == slider.url)
                            {
                                slider.getFrame().onload = function(){
                                    slider.getFrame().contentWindow.document.body.querySelector('input[name="EMAIL[n0][VALUE]"]').value = email;
                                }
                            }
                        }
                    }
                };
            }
        });
    }
};

AddButtonCrm.deleteOldCrmButton = function(crmButtons) {
    crmButtons.forEach(function(button) {
        // Находим ближайший родительский элемент <td> и скрываем его
        let td = button.closest('td');
        if (td) {
            td.style.display = 'none';
        }
    });
};