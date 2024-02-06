var AddButtonContact = BX.namespace('AddButtonContact');
AddButtonContact.init = function(){
    this.createAddContactButton();

    BX.addCustomEvent( "BX.Main.grid:paramsUpdated", BX.delegate(function(data){
        AddButtonContact.createAddContactButton();
        })
    );

};
AddButtonContact.createAddContactButton = function() {

    var panelWidget = BX.findChildByClassName(document, 'main-grid-table');
    var panelCategoryWidget = BX.findChild(panelWidget, { tag: 'tbody'});
    var arrMail = BX.findChild(panelCategoryWidget, { tag: 'tr', class: 'main-grid-row main-grid-row-body'}, true, true);

    if (arrMail != null){
        arrMail.shift();
    }

    if (arrMail != null) {
        arrMail.forEach(function (elem) {

            let gridСell = elem.querySelectorAll('.main-grid-cell-left');

            //Проверка на наличие кнопки "Создать контакт"
            for (let index = 0; index < gridСell.length; index++) {
                if (gridСell[index].querySelectorAll('.mail-binding-contact').length !== 0){
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
                let email = splitNameTitle[1];

                let creatNewContactButton = BX.create(
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
                                'label',
                                {
                                    attrs: {
                                        className: 'sender-add-template-label',
                                        id: 'auto-label',
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
                                                                            className: 'mail-ui-binding ui-btn-light-border ui-btn ui-btn-xs ui-btn-round ui-btn-no-caps mail-binding-contact mail-ui-not-active js-bind-'+idMessage,
                                                                            id: 'mess'+idMessage,
                                                                            title: 'Создать Контакт',
                                                                        },
                                                                        dataset:{
                                                                            email: email,
                                                                        },
                                                                        text: 'Создать контакт',
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
                            ),
                        ],
                    }
                );

                elem.insertBefore(creatNewContactButton,gridСell[3]);
                let buttonNewContact = creatNewContactButton.querySelector('a#mess'+idMessage);
                buttonNewContact.onclick = function(){
                    let email = buttonNewContact.getAttribute('data-email');
                    const url = "/crm/contact/details/0/?EMAIL=" + email+'&NAME='+name;
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
                };
            }

        });
    }
};